<?php

return [
    'map' => [
        'department'      => [ 'job' => \App\Jobs\SyncDepartmentJob::class,     'queue' => 'sync_departments' ],
        'employee'        => [ 'job' => \App\Jobs\SyncEmployeeJob::class,       'queue' => 'sync_employees' ],
        'skill'           => [ 'job' => \App\Jobs\SyncSkillJob::class,          'queue' => 'sync_skills' ],
        'employee_skill'  => [ 'job' => \App\Jobs\SyncEmployeeSkillJob::class,  'queue' => 'sync_employee_skills' ],
    ],
    'connection' => env('OUTBOX_QUEUE_CONNECTION', 'rabbitmq'),
];
