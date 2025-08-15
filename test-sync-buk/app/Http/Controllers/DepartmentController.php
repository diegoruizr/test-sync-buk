<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the Departments.
     * route: GET /api/system-rrhh/departments
     * 
     * @param Request $request
     */
    public function index(Request $request)
    {
        $query = Department::query();

        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        }

        if ($since = $request->query('updated_since')) {
            $query->where('updated_at', '>=', $since);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('cost_center_code', 'ilike', "%{$search}%");
            });
        }

        $perPage = (int) ($request->query('per_page', 15));
        return DepartmentResource::collection($query->orderBy('updated_at','desc')->paginate($perPage));
    }

    /**
     * Show the specified department.
     * route: /api/system-rrhh/departments/{department}
     * 
     * @param Department $department
     */
    public function show(Department $department)
    {
        return new DepartmentResource($department);
    }

    /**
     * Store a newly created department.
     * route: /api/system-rrhh/departments
     * 
     * @param StoreDepartmentRequest $request
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified department.
     * route: /api/system-rrhh/departments/{department}
     * 
     * @param UpdateDepartmentRequest $request
     * @param Department $department
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->fill($request->validated())->save();

        return new DepartmentResource($department);
    }

    /**
     * Soft delete the specified department.
     * route: /api/system-rrhh/departments/{department}
     *
     * @param Department $department
     */
    public function destroy(Department $department)
    {
        if ($department->employees()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el departamento: tiene empleados asociados. Reasigna o desasigna primero.',
                'code' => 'DEPT_HAS_EMPLOYEES'
            ], 409);
        }
        $department->delete();

        return response()->noContent();
    }
}
