<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SkillController extends Controller
{
    /**
     * Get a list of skills.
     * route: GET /api/system-rrhh/skills
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $q = Skill::query();

        if ($request->boolean('with_trashed')) {
            $q->withTrashed();
        } elseif ($request->boolean('only_trashed')) {
            $q->onlyTrashed();
        }

        if ($since = $request->query('updated_since')) {
            $q->where('updated_at', '>=', $since);
        }

        if ($search = $request->query('q')) {
            $q->where('name', 'ilike', "%{$search}%");
        }

        if (!is_null($request->query('level_required'))) {
            $q->where('level_required', (int) $request->query('level_required'));
        }

        $perPage = (int) $request->query('per_page', 15);

        return SkillResource::collection(
            $q->orderBy('updated_at','desc')->paginate($perPage)
        );
    }

    /**
     * Get a specific skill.
     * route: GET /api/system-rrhh/skills/{skill}
     *
     * @param Skill $skill
     */
    public function show(Skill $skill)
    {
        return new SkillResource($skill);
    }

    /**
     * Get a specific skill.
     * route: GET /api/system-rrhh/skills/{skill}
     *
     * @param Skill $skill
     */
    public function store(StoreSkillRequest $request)
    {
        $skill = Skill::create($request->validated());

        return (new SkillResource($skill))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get a specific skill.
     * route: GET /api/system-rrhh/skills/{skill}
     *
     * @param UpdateSkillRequest $request
     * @param Skill $skill
     */
    public function update(UpdateSkillRequest $request, Skill $skill)
    {
        $skill->fill($request->validated())->save();

        return new SkillResource($skill);
    }

    /**
     * Get a specific skill.
     * route: DELETE /api/system-rrhh/skills/{skill}
     *
     * @param Skill $skill
     */
    public function destroy(Skill $skill)
    {
        $employees = $skill->employees()->wherePivotNull('deleted_at')->exists();

        if ($employees) {
            return response()->json([
                'message' => 'No se puede eliminar la habilidad: hay empleados que aún la tienen asignada. Desasigna primero.',
                'code'    => 'SKILL_HAS_EMPLOYEES',
            ], 409);
        }

        $skill->delete();
        return response()->noContent();
    }
}
