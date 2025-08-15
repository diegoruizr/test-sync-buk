<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $fillable = ['id', 'name', 'cost_center_code'];
    protected $casts = [
        'id'         => 'string',
        'deleted_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get the employees for the department.
     *
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
