<?php

namespace App\Constants;

class GlobalConstants
{
    // Estados de empleados
    public const EMPLOYEE_STATUS_ACTIVE = 'active';
    public const EMPLOYEE_STATUS_INACTIVE = 'inactive';
    public const EMPLOYEE_STATUS_SUSPENDED = 'suspended';

    // Estrategias de skills
    public const SKILL_STRATEGY_MERGE = 'merge';
    public const SKILL_STRATEGY_REPLACE = 'replace';

    // Eventos de sincronización
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_RESTORED = 'restored';

    // Colas RabbitMQ
    public const QUEUE_SYNC_DEPARTMENTS = 'sync_departments';
    public const QUEUE_SYNC_EMPLOYEES = 'sync_employees';
    public const QUEUE_SYNC_EMPLOYEE_SKILLS = 'sync_employee_skills';
    public const QUEUE_SYNC_SKILLS = 'sync_skills';

    //Outbox
    public const OUTBOX = 'outbox';

    // Lotes para procesamiento
    public const OUTBOX_DEFAULT_BATCH_SIZE = 200;
    public const SYNC_DEFAULT_RETRY_DELAY = 10;

    //Jobs
    public const JOB_TRIES = 5;
    public const JOB_BACKOFF = [10, 20, 40, 60];

}
