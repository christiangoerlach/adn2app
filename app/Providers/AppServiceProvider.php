<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $projectId = Cache::get('current_project_id');
        if ($projectId) {
            config(['app.current_project_id' => (string) $projectId]);
        }

        // Custom Azure Disk, die den Container dynamisch je nach Projekt setzt
        Storage::extend('azureblob', function ($app, $config) {
            $connectionString = $config['connection_string'] ?? env('AZURE_STORAGE_CONNECTION_STRING');
            if (!$connectionString || trim((string) $connectionString) === '') {
                $account = $config['account'] ?? env('AZURE_STORAGE_ACCOUNT');
                $key = $config['key'] ?? env('AZURE_STORAGE_KEY');
                $suffix = $config['endpoint_suffix'] ?? env('AZURE_STORAGE_ENDPOINT_SUFFIX', 'core.windows.net');
                if ($account && $key) {
                    $connectionString = 'DefaultEndpointsProtocol=https;AccountName=' . $account . ';AccountKey=' . $key . ';EndpointSuffix=' . $suffix;
                }
            }
            $client = BlobRestProxy::createBlobService($connectionString);

            $container = $config['container'] ?? null;
            if (!$container) {
                // aus aktueller Projektwahl (Session/Cache) holen
                $container = (string) (config('app.current_project_id_container') ?? '');
            }

            // Falls kein Container aus Config gesetzt wurde, versuche aus DB anhand current_project_id
            if ($container === '') {
                try {
                    $currentProjectId = (string) (config('app.current_project_id') ?? '');
                    if ($currentProjectId !== '') {
                        $record = $app['db']->table('projects')->select('BilderContainer')->where('Id', $currentProjectId)->first();
                        if ($record && isset($record->BilderContainer)) {
                            $container = (string) $record->BilderContainer;
                        }
                    }
                } catch (\Throwable $e) {
                    // im Boot-Prozess nicht hart fehlschlagen
                }
            }

            $adapter = new AzureBlobStorageAdapter($client, $container ?: '');
            $filesystem = new Flysystem($adapter);
            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
    }
}
