<?php

namespace App\Models;

use App\Jobs\SyncDepartmentJob;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'cost_center_code'];

    protected static function booted()
    {
        static::created(function ($department) {
            SyncDepartmentJob::dispatch($department->toArray());
        });
    }
}
