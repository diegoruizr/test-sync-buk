<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeSkill extends Pivot
{
    use SoftDeletes;

    protected $table = 'employee_skills';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    protected $primaryKey = null;
    protected $fillable = ['employee_id','skill_id','level'];
    protected $casts = [
        'employee_id' => 'string',
        'skill_id'    => 'string',
        'level'       => 'integer',
        'deleted_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'created_at'  => 'datetime',
    ];
    public $incrementing = false;
    public $timestamps = true;

    /**
     * Get the keys for the save query.
     *
     * @param [type] $query
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('employee_id', $this->getAttribute('employee_id'))
            ->where('skill_id', $this->getAttribute('skill_id'));
    }
}
