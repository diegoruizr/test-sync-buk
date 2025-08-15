<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SkillResource;
use App\Models\Skill;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    /**
     * Index Skill.
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $q       = $request->query('q');
        $from    = $request->query('from');
        $to      = $request->query('to');
        $sort    = $request->query('sort', 'updated_at');
        $dir     = $request->query('dir', 'desc');

        $query = Skill::query()
            ->select(['id','name','level_required','created_at','updated_at'])
            ->when($q,    fn($qq) => $qq->where('name', 'ilike', '%'.$q.'%'))
            ->when($from, fn($qq) => $qq->whereDate('updated_at', '>=', $from))
            ->when($to,   fn($qq) => $qq->whereDate('updated_at', '<=', $to))
            ->orderBy($sort, $dir);

        $skills = $query->paginate($perPage);

        return SkillResource::collection($skills);
    }

    /**
     * Show Skill.
     *
     * @param Skill $skill
     */
    public function show(Skill $skill)
    {
        return new SkillResource($skill);
    }
}
