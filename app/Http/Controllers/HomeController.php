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

        // Bilder und Azure-URLs für das aktuell gewählte Projekt laden
        $currentContainerName = null;
        $images = [];
        $blobBaseUrl = null;
        if ($currentProjectId !== '') {
            $currentContainerName = DB::table('projects')
                ->where('Id', $currentProjectId)
                ->value('BilderContainer');

            $imageRows = DB::table('bilder')
                ->select(['Id', 'FileName', 'CreatedAt'])
                ->where('ProjectsId', $currentProjectId)
                ->orderBy('CreatedAt', 'desc')
                ->get();

            $baseUrl = env('BLOB_BASE_URL');
            if (!$baseUrl || trim((string) $baseUrl) === '') {
                $account = env('AZURE_STORAGE_ACCOUNT');
                $suffix = env('AZURE_STORAGE_ENDPOINT_SUFFIX', 'core.windows.net');
                if ($account) {
                    $baseUrl = 'https://' . $account . '.blob.' . $suffix;
                }
            }
            $blobBaseUrl = $baseUrl;

            foreach ($imageRows as $row) {
                $url = null;
                if ($baseUrl && $currentContainerName && $row->FileName) {
                    $url = rtrim((string) $baseUrl, '/') . '/' . trim((string) $currentContainerName, '/') . '/' . rawurlencode((string) $row->FileName);
                }
                $images[] = [
                    'id' => $row->Id,
                    'fileName' => (string) $row->FileName,
                    'url' => $url,
                    'fullUrl' => $url,
                ];
            }
        }

        return view('welcome', [
            'projects' => $projects,
            'currentProjectId' => $currentProjectId,
            'currentProjectName' => $currentProjectName,
            'currentContainerName' => $currentContainerName,
            'images' => $images,
            'blobBaseUrl' => $blobBaseUrl,
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



