<?php

namespace App\Http\Controllers;

use App\Constants\GlobalConstants;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\Employees\ApplyEmployeeSkills;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     * route: GET /api/system-rrhh/employees
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        if ($since = $request->query('updated_since')) {
            $query->where('updated_at', '>=', $since);
        }

        if ($dept = $request->query('department_id')) {
            $query->where('department_id', $dept);
        }
        if (!is_null($request->query('is_active'))) {
            $query->where('is_active', (bool) $request->query('is_active'));
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('position', 'ilike', "%{$search}%");
            });
        }

        $perPage = (int) ($request->query('per_page', 15));

        return EmployeeResource::collection(
            $query->orderBy('updated_at','desc')->paginate($perPage)
        );
    }

    /**
     * Display the specified employee.
     * route: GET /api/system-rrhh/employees/{employee}
     *
     * @param Employee $employee
     */
    public function show(Employee $employee)
    {
        if (request()->boolean('include_skills')) {
            $employee->load(['skills' => function ($q) {
                $q->withPivot(['level', 'created_at', 'updated_at', 'deleted_at']);
            }]);
        }
        if (request()->boolean('include_department')) {
            $employee->load('department');
        }
        return new EmployeeResource($employee);
    }

    /**
     * Store a newly created employee.
     * route: POST /api/system-rrhh/employees
     *
     * @param StoreEmployeeRequest $request
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        if ($skills = $request->input('skills')) {
            $strategy = $request->input('skills_strategy', GlobalConstants::SKILL_STRATEGY_REPLACE);
            app(ApplyEmployeeSkills::class)->handle($employee, $skills, $strategy);
        }

        if ($request->boolean('include_skills')) {
            $employee->load(['skills' => function ($q) {
                $q->withPivot(['level','deleted_at','created_at','updated_at']);
            }]);
        }

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified employee.
     * route: PUT/PATCH /api/system-rrhh/employees/{employee}
     *
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->fill($request->validated())->save();

        if ($skills = $request->input('skills')) {
            $strategy = $request->input('skills_strategy', GlobalConstants::SKILL_STRATEGY_REPLACE);
            app(ApplyEmployeeSkills::class)->handle($employee, $skills, $strategy);
        }

        if ($request->boolean('include_skills')) {
            $employee->load(['skills' => function ($q) {
                $q->withPivot(['level','deleted_at','created_at','updated_at']);
            }]);
        }

        return new EmployeeResource($employee);
    }

    /**
     * Soft delete the specified employee.
     * route: DELETE /api/system-rrhh/employees/{employee}
     *
     * @param Employee $employee
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->noContent();
    }
}
