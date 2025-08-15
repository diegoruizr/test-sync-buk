<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Index Employee
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $perPage    = (int) ($request->query('per_page', 15));
        $q          = $request->query('q');
        $department = $request->query('department');
        $active     = $request->query('active');
        $from       = $request->query('from');
        $to         = $request->query('to');
        $sort       = $request->query('sort', 'updated_at');
        $dir        = $request->query('dir', 'desc');

        $query = Employee::query()
            ->select(['id','name','email','position','hire_date','department_id','is_active','created_at','updated_at'])
            ->with(['department'])
            ->when($q, fn($qq) => $qq->where(function($w) use ($q){
                $w->where('name','ilike',"%{$q}%")
                  ->orWhere('email','ilike',"%{$q}%")
                  ->orWhere('position','ilike',"%{$q}%");
            }))
            ->when($department, fn($qq) => $qq->where('department_id', $department))
            ->when($active !== null && $active !== '', fn($qq) => $qq->where('is_active', (bool)$active))
            ->when($from, fn($qq) => $qq->whereDate('updated_at','>=',$from))
            ->when($to,   fn($qq) => $qq->whereDate('updated_at','<=',$to))
            ->orderBy($sort, $dir);

        $employees = $query->paginate($perPage);

        return EmployeeResource::collection($employees);
    }

    /**
     * Show Employee.
     *
     * @param Employee $employee
     */
    public function show(Employee $employee)
    {
        $employee->load(['skills' => function ($q) {
            $q->withPivot(['level', 'created_at', 'updated_at', 'deleted_at']);
        }]);
        $employee->load(['department']);
        return new EmployeeResource($employee);
    }
}
