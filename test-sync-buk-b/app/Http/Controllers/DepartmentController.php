<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Index Department.
     */
     public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $q       = $request->query('q');
        $from    = $request->query('from');
        $to      = $request->query('to');
        $sort    = $request->query('sort', 'updated_at');
        $dir     = $request->query('dir', 'desc');

        $query = Department::query()
            ->select(['id','name','cost_center_code','created_at','updated_at'])
            ->when($q, fn($qq) => $qq->where('name', 'ilike', '%'.$q.'%'))
            ->when($from, fn($qq) => $qq->whereDate('updated_at', '>=', $from))
            ->when($to, fn($qq) => $qq->whereDate('updated_at', '<=', $to))
            ->orderBy($sort, $dir);

        $departments = $query->paginate($perPage);

        return DepartmentResource::collection($departments);
    }


    /**
     * Show Department.
     */
    public function show(Department $department)
    {
        return new DepartmentResource($department);
    }
}
