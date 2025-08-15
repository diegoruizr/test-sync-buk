<?php

use App\Jobs\SyncDepartmentJob;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function deptPayload(array $overrides = []): array
{
    $base = [
        'id'               => (string) Str::uuid(),
        'name'             => 'IT',
        'cost_center_code' => 'CC-001',
        'created_at'       => '2025-08-10T10:00:00Z',
        'updated_at'       => '2025-08-10T10:00:00Z',
        'deleted_at'       => null,
    ];

    return array_merge($base, $overrides);
}

function runJob(string $event, array $data): void
{
    // Ejecutamos la lógica directamente (no por queue) para probar idempotencia
    (new SyncDepartmentJob($event, $data))->handle();
}

it('crea un registro nuevo con los timestamps del evento', function () {
    $p = deptPayload();
    runJob('created', $p);

    $d = Department::withTrashed()->findOrFail($p['id']);

    expect($d->name)->toBe('IT')
        ->and($d->cost_center_code)->toBe('CC-001');

    expect($d->updated_at->equalTo(Carbon::parse('2025-08-10T10:00:00Z')))->toBeTrue();
    expect($d->created_at->equalTo(Carbon::parse('2025-08-10T10:00:00Z')))->toBeTrue();
    expect($d->deleted_at)->toBeNull();
});

it('es idempotente ante un duplicado (mismo updated_at)', function () {
    $p = deptPayload(['name' => 'IT v1']);
    runJob('created', $p);

    // Duplicado exacto (mismo updated_at): NO debe cambiar nada
    $p2 = $p;
    $p2['name'] = 'IT v1 DUP';
    runJob('updated', $p2);

    $d = Department::withTrashed()->findOrFail($p['id']);

    expect($d->name)->toBe('IT v1'); // no cambió
    expect($d->updated_at->equalTo(Carbon::parse('2025-08-10T10:00:00Z')))->toBeTrue();
});

it('aplica un update solo si la versión entrante es mayor', function () {
    $id = (string) Str::uuid();

    // Estado inicial T1
    $p1 = deptPayload([
        'id' => $id,
        'name' => 'IT T1',
        'updated_at' => '2025-08-10T10:00:00Z',
    ]);
    runJob('created', $p1);

    // Evento viejo T0 (debe ignorar)
    $p0 = $p1;
    $p0['name'] = 'IT T0';
    $p0['updated_at'] = '2025-08-10T09:00:00Z';
    runJob('updated', $p0);

    // Evento nuevo T2 (debe aplicar)
    $p2 = $p1;
    $p2['name'] = 'IT T2';
    $p2['updated_at'] = '2025-08-10T11:00:00Z';
    runJob('updated', $p2);

    $d = Department::withTrashed()->findOrFail($id);

    expect($d->name)->toBe('IT T2');
    expect($d->updated_at->equalTo(Carbon::parse('2025-08-10T11:00:00Z')))->toBeTrue();
});

it('aplica soft delete con deleted_at del evento y preserva orden frente a updates viejos', function () {
    $id = (string) Str::uuid();

    // Crear T1
    $p1 = deptPayload([
        'id' => $id,
        'name' => 'IT',
        'updated_at' => '2025-08-10T10:00:00Z',
    ]);
    runJob('created', $p1);

    // DELETE T3
    $del = $p1;
    $del['deleted_at'] = '2025-08-10T12:00:00Z';
    runJob('deleted', $del);

    $trashed = Department::onlyTrashed()->findOrFail($id);
    expect($trashed->deleted_at->equalTo(Carbon::parse('2025-08-10T12:00:00Z')))->toBeTrue();

    // UPDATE T2 (viejo respecto a T3) → debe ignorar y seguir borrado
    $p2 = $p1;
    $p2['name'] = 'IT T2';
    $p2['updated_at'] = '2025-08-10T11:00:00Z';
    runJob('updated', $p2);

    $stillTrashed = Department::onlyTrashed()->findOrFail($id);
    expect($stillTrashed->name)->toBe('IT'); // no cambió
    expect($stillTrashed->deleted_at->equalTo(Carbon::parse('2025-08-10T12:00:00Z')))->toBeTrue();
});

it('restaura si la versión del RESTORE es mayor que el delete', function () {
    $id = (string) Str::uuid();

    // Crear T1
    runJob('created', deptPayload([
        'id' => $id,
        'name' => 'IT',
        'updated_at' => '2025-08-10T10:00:00Z',
    ]));

    // DELETE T3
    runJob('deleted', deptPayload([
        'id' => $id,
        'deleted_at' => '2025-08-10T12:00:00Z',
    ]));

    // RESTORE T4 (mayor que T3)
    runJob('restored', deptPayload([
        'id' => $id,
        'updated_at' => '2025-08-10T13:00:00Z',
        'deleted_at' => null,
    ]));

    $d = Department::findOrFail($id);

    expect($d->deleted_at)->toBeNull();
    expect($d->updated_at->equalTo(Carbon::parse('2025-08-10T13:00:00Z')))->toBeTrue();
});

it('preserva exactamente los timestamps del evento (no usa now)', function () {
    $id = (string) Str::uuid();

    runJob('created', deptPayload([
        'id' => $id,
        'updated_at' => '2025-08-10T10:10:10Z',
        'created_at' => '2025-08-10T10:00:00Z',
    ]));

    $d = Department::withTrashed()->findOrFail($id);

    expect($d->updated_at->equalTo(Carbon::parse('2025-08-10T10:10:10Z')))->toBeTrue();
    expect($d->created_at->equalTo(Carbon::parse('2025-08-10T10:00:00Z')))->toBeTrue();
});

it('en cola: se publica en la queue sync_departments', function () {
    Queue::fake();

    $p = deptPayload();

    // OJO: aquí probamos dispatch, no handle(); el Job debe fijar la cola en constructor o al despachar
    SyncDepartmentJob::dispatch('created', $p)->afterCommit();

    Queue::assertPushedOn('sync_departments', SyncDepartmentJob::class);
    Queue::assertPushed(SyncDepartmentJob::class, function ($job) use ($p) {
        return $job->event === 'created' && $job->data['id'] === $p['id'];
    });
});
