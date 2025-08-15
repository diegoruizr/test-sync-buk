<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeSkillResource;
use App\Models\Employee;
use App\Models\EmployeeSkill;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeSkillController extends Controller
{
    /**
     * Store a newly created skill for the specified employee.
     * route: POST /api/system-rrhh/employees/{employee}/skills
     *
     * @param Employee $employee
     * @param Request $request
     */
    public function store(Employee $employee, Request $request)
    {
        $data = $request->validate([
            'skill_id' => ['required','uuid','exists:skills,id'],
            'level'    => ['required','integer','min:0'],
        ]);

        $employee->skills()->syncWithoutDetaching([
            $data['skill_id'] => ['level' => $data['level'], 'deleted_at' => null],
        ]);

        $pivot = EmployeeSkill::where('employee_id', $employee->id)
            ->where('skill_id', $data['skill_id'])->firstOrFail();

        return (new EmployeeSkillResource($pivot))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified skill for the employee.
     * route: PATCH /api/system-rrhh/employees/{employee}/skills/{skill}
     *
     * @param Employee $employee
     * @param string $skillId
     * @param Request $request
     */
    public function update(Employee $employee, string $skillId, Request $request)
    {
        $data = $request->validate([
            'level' => ['required','integer','min:0'],
        ]);

        $employee->skills()->updateExistingPivot($skillId, ['level' => $data['level']]);
        $pivot = EmployeeSkill::where('employee_id',$employee->id)
            ->where('skill_id',$skillId)->firstOrFail();

        return new EmployeeSkillResource($pivot);
    }

    /**
     * Soft delete the specified skill for the employee.
     * route: DELETE /api/system-rrhh/employees/{employee}/skills/{skill}
     *
     * @param Employee $employee
     * @param string $skillId
     */
    public function destroy(Employee $employee, string $skillId)
    {
        $employee->skills()->updateExistingPivot($skillId, ['deleted_at' => now()]);

        return response()->noContent();
    }
}
