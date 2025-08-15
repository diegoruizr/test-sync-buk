<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Skill extends Model
{
     use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'skills';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $keyType = 'string';
    protected $fillable = ['name', 'level_required'];
    protected $casts = [
        'id'              => 'string',
        'level_required'  => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    public $incrementing = false;

    /**
     * Get the employees for the skill.
     *
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_skills')
            ->using(\App\Models\EmployeeSkill::class)
            ->withPivot(['level','created_at','updated_at','deleted_at'])
            ->withTimestamps()
            ->as('assignment');
    }
}
