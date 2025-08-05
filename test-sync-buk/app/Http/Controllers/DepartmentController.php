<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
     public function store(Request $request)
    {
        // Validar entrada
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost_center_code' => 'required|string|max:255',
        ]);

        // Crear el departamento
        $department = Department::create([
            'name' => $validated['name'],
            'cost_center_code' => $validated['cost_center_code'],
        ]);

        return response()->json([
            'message' => 'Departamento creado y evento enviado.',
            'department' => $department
        ]);
    }
}
