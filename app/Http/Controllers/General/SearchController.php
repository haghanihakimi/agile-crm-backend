<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search($orgId, Request $request)
    {
        $query = $request->input('query');

        $org = Organization::where('org_uuid', $orgId)->first();

        if (!$org) {
            return response()->json([
                "code" => 404,
                "message" => 'Incorrect organization entry!',
                "results" => []
            ], 404);
        }

        if (strlen($query) < 3) {
            return response()->json([
                "code" => 200,
                "message" => '',
                "results" => []
            ], 200);
        }

        $members = $org->users()->where('firstname', 'LIKE', '%' . $query . '%')
            ->orWhere('lastname', 'LIKE', '%' . $query . '%')
            ->orWhere('email', 'LIKE', '%' . $query . '%')->limit(10)->get()->unique('id');

        $projects = $org->projects()->where('title', 'LIKE', '%' . $query . '%')
        ->limit(10)->get()->unique('project_uuid');

        $tasks = $org->tasks()->where('title', 'LIKE', '%' . $query . '%')
        ->limit(10)->get()->unique('id');

        $results = $members->merge($tasks)->merge($projects);

        return response()->json([
            "code" => 200,
            "message" => '',
            "results" => $results
        ], 200);
    }
}
