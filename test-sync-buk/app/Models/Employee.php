<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'employees';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $fillable = [
        'name',
        'email',
        'position',
        'hire_date',
        'department_id',
        'is_active',
    ];
    protected $casts = [
        'id'           => 'string',
        'hire_date'    => 'date',
        'is_active'    => 'boolean',
        'deleted_at'   => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    /**
     * Get the department that owns the employee.
     *
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the skills assigned to the employee.
     *
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'employee_skills')
            ->using(\App\Models\EmployeeSkill::class)
            ->withPivot(['level','created_at','updated_at','deleted_at'])
            ->withTimestamps()
            ->as('assignment');
    }
}
