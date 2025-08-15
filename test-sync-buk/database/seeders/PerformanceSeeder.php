<?php

namespace Database\Seeders;

use App\Http\Resources\DepartmentResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeSkillResource;
use App\Http\Resources\SkillResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use App\Models\Skill;
use App\Services\Outbox\OutboxRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class PerformanceSeeder extends Seeder
{
    private int $DEPTS;
    private int $SKILLS;
    private int $EMPS;
    private int $MIN_SK;
    private int $MAX_SK;
    private int $CHUNK;

    public function __construct()
    {
        $this->DEPTS  = (int) env('SEED_DEPARTMENTS', 8);
        $this->SKILLS = (int) env('SEED_SKILLS', 15);
        $this->EMPS   = (int) env('SEED_EMPLOYEES', 200);
        $this->MIN_SK = (int) env('SEED_MIN_SKILLS', 1);
        $this->MAX_SK = (int) env('SEED_MAX_SKILLS', 3);
        $this->CHUNK  = (int) env('SEED_CHUNK', 200);
    }

    public function run(): void
    {
        Model::unsetEventDispatcher();
        DB::disableQueryLog();

        // 1) Crear catálogos
        $departments = Department::factory($this->DEPTS)->create();
        $skills      = Skill::factory($this->SKILLS)->create();

        // Grabar outbox "created" para catálogos
        foreach ($departments as $d) {
            OutboxRecorder::record('department', $d->id, 'created', (new DepartmentResource($d))->resolve());
        }
        foreach ($skills as $s) {
            OutboxRecorder::record('skill', $s->id, 'created', (new SkillResource($s))->resolve());
        }

        $deptIds  = $departments->pluck('id')->all();
        $skillIds = $skills->pluck('id')->all();

        // 2) Crear empleados en chunks y asignar skills
        $toCreate = $this->EMPS;
        while ($toCreate > 0) {
            $n = min($this->CHUNK, $toCreate);

            // Creamos empleados
            $employees = Employee::factory($n)->make()->each(function (Employee $e) use ($deptIds) {
                $e->department_id = Arr::random($deptIds);
            });

            // Guardar + outbox por cada employee
            foreach ($employees as $e) {
                DB::transaction(function () use ($e, $skillIds) {
                    $e->save(); 
                    OutboxRecorder::record('employee', $e->id, 'created', (new EmployeeResource($e))->resolve());

                    // Asignaciones pivote iniciales: insert directo + outbox "created" por cada una
                    $pick = Arr::random($skillIds, random_int($this->MIN_SK, $this->MAX_SK));
                    foreach ((array)$pick as $sid) {
                        $now = Carbon::now()->format('Y-m-d H:i:s.u');

                        DB::table('employee_skills')->insert([
                            'employee_id' => $e->id,
                            'skill_id'    => $sid,
                            'level'       => random_int(1, 10),
                            'created_at'  => $now,
                            'updated_at'  => $now,
                            'deleted_at'  => null,
                        ]);

                        // Payload del pivote para outbox
                        $p = new EmployeeSkill([
                            'employee_id' => $e->id,
                            'skill_id'    => $sid,
                            'level'       => 0,
                        ]);
                        $p->exists = true;
                        $p->setAttribute('level', DB::table('employee_skills')
                            ->where('employee_id', $e->id)->where('skill_id', $sid)->value('level'));
                        $p->setAttribute('created_at', $now);
                        $p->setAttribute('updated_at', $now);
                        $p->setAttribute('deleted_at', null);

                        $payload = (new EmployeeSkillResource($p))->resolve();
                        // aggregate_id determinístico para la pivote
                        $aggId = Uuid::uuid5(Uuid::NAMESPACE_URL, $e->id.'|'.$sid)->toString();
                        OutboxRecorder::record('employee_skill', $aggId, 'created', $payload);
                    }
                });
            }

            $toCreate -= $n;
        }

        // 3) Ráfaga de cambios para estresar
        if (env('SEED_STRESS_EVENTS', true)) {
            $this->stressEvents();
        }
    }

    private function stressEvents(): void
    {
        // Tomamos ~10% de empleados:
        // - update de nivel en 1 skill
        // - delete de otra skill
        // - restore posterior de esa misma skill con otro nivel
        $employees = Employee::query()->inRandomOrder()->limit((int) max(1, $this->EMPS * 0.1))->get();

        foreach ($employees as $e) {
            // Skills actuales
            $skills = DB::table('employee_skills')
                ->where('employee_id', $e->id)
                ->whereNull('deleted_at')
                ->pluck('skill_id')
                ->all();

            if (count($skills) < 1) continue;

            // UPDATE nivel en una
            $sidUpdate = Arr::random($skills);
            $newLevel  = random_int(1, 10);
            $tsU = Carbon::now()->addMicrosecond()->format('Y-m-d H:i:s.u');
            DB::table('employee_skills')->where('employee_id', $e->id)->where('skill_id', $sidUpdate)
                ->update(['level' => $newLevel, 'updated_at' => $tsU]);

            $p1 = EmployeeSkill::query()->where('employee_id', $e->id)->where('skill_id', $sidUpdate)->first();
            if ($p1) {
                $payload = (new EmployeeSkillResource($p1))->resolve();
                $aggId = Uuid::uuid5(Uuid::NAMESPACE_URL, $e->id.'|'.$sidUpdate)->toString();
                OutboxRecorder::record('employee_skill', $aggId, 'updated', $payload);
            }

            if (count($skills) >= 2) {
                // DELETE en otra
                $sidDel = Arr::random(array_values(array_diff($skills, [$sidUpdate])));
                $tsD = Carbon::now()->addMicrosecond()->format('Y-m-d H:i:s.u');
                DB::table('employee_skills')->where('employee_id', $e->id)->where('skill_id', $sidDel)
                    ->update(['deleted_at' => $tsD, 'updated_at' => $tsD]);

                $p2 = EmployeeSkill::withTrashed()
                    ->where('employee_id', $e->id)->where('skill_id', $sidDel)->first();
                if ($p2) {
                    $payload = (new EmployeeSkillResource($p2))->resolve();
                    $aggId = Uuid::uuid5(Uuid::NAMESPACE_URL, $e->id.'|'.$sidDel)->toString();
                    OutboxRecorder::record('employee_skill', $aggId, 'deleted', $payload);

                    // RESTORE inmediatamente con otro level
                    $newLevel2 = random_int(1, 10);
                    $tsR = Carbon::now()->addMicrosecond()->format('Y-m-d H:i:s.u');
                    DB::table('employee_skills')->where('employee_id', $e->id)->where('skill_id', $sidDel)
                        ->update(['deleted_at' => null, 'level' => $newLevel2, 'updated_at' => $tsR]);

                    $p3 = EmployeeSkill::query()
                        ->where('employee_id', $e->id)->where('skill_id', $sidDel)->first();
                    if ($p3) {
                        $payload = (new EmployeeSkillResource($p3))->resolve();
                        OutboxRecorder::record('employee_skill', $aggId, 'restored', $payload);
                        // y un updated final para empujar versión
                        OutboxRecorder::record('employee_skill', $aggId, 'updated', $payload);
                    }
                }
            }
        }
    }
}
