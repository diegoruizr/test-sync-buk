<?php

namespace App\Models;

use App\Jobs\SyncDepartmentJob;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $fillable = ['name', 'cost_center_code'];
    protected $casts = [
        'id'         => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the employees for the department.
     *
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
