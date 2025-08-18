<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $projects = DB::table('projects')
            ->select(['Id', 'Projektname'])
            ->orderBy('Projektname')
            ->get();

        $currentProjectId = (string) ($request->session()->get('current_project_id') ?? Cache::get('current_project_id', ''));

        $currentProjectName = 'n/a';
        if ($currentProjectId !== '') {
            $found = $projects->first(function ($p) use ($currentProjectId) {
                return (string) $p->Id === (string) $currentProjectId;
            });
            if ($found) {
                $currentProjectName = (string) ($found->Projektname ?? 'n/a');
            }
        }

        return view('welcome', [
            'projects' => $projects,
            'currentProjectId' => $currentProjectId,
            'currentProjectName' => $currentProjectName,
        ]);
    }

    public function selectProject(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required'],
        ]);

        $projectId = (string) $validated['project_id'];

        $request->session()->put('current_project_id', $projectId);
        Cache::forever('current_project_id', $projectId);

        // Optional: zur Laufzeit in die Config setzen ("Umgebungsvariable"-ähnlich)
        config(['app.current_project_id' => $projectId]);

        return Redirect::back();
    }
}



