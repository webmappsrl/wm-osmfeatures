<?php

namespace Wm\WmOsmfeatures\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;


trait OsmfeaturesCommandHelper
{
    /**
     * Return an array of the model's names that implement the OsmfeaturesSyncableTrait
     * @param string $trait
     * 
     * @return array
     */
    protected function getInitializedModels(string $trait): array
    {
        $modelDirectory = app_path('/Models');

        //get all the files in the model directory
        $modelFiles = File::files($modelDirectory);

        if (empty($modelFiles)) {
            throw WmOsmfeaturesException::missingModels();
        }

        $models = [];

        foreach ($modelFiles as $file) {
            //get the file content
            $content = File::get($file);

            //check if the content contains the trait
            if (Str::contains($content, $trait)) {
                //get the model name
                $model = Str::before($file->getFilename(), '.php');

                $models[] = $model;
            }
        }

        return $models;
    }

    /**
     * Initialize the given database table for osmfeatures sync. Adding osmfeatures_id, osmfeatures_data, osmfeatures_updated_at columns
     */
    protected function initializeTable(string $table): void
    {
        //get the table schema
        $schema = DB::getSchemaBuilder();

        //check if the table exists
        if (!$schema->hasTable($table)) {
            throw WmOsmfeaturesException::missingTable($table);
        }

        if ($schema->hasColumns($table, ['osmfeatures_id', 'osmfeatures_data', 'osmfeatures_updated_at'])) {
            Log::info("Table $table already initialized");
            $this->info("Table $table already initialized, skipping");

            return;
        }

        if (!in_array('osmfeatures_id', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_id varchar(255)");
        }

        if (!in_array('osmfeatures_data', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_data jsonb");
        }

        if (!in_array('osmfeatures_updated_at', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_updated_at timestamp");
        }
        $this->info("Table $table initialized for the osmfeatures sync");
    }

    /**
     * Check if the given model has all the required fillables
     *
     *
     * @throws WmOsmfeaturesException
     */
    protected function checkFillables(string $className): bool
    {
        $instance = new $className;

        $fillable = $instance->getFillable();

        $osmFeaturesAttributes = ['osmfeatures_id', 'osmfeatures_data', 'osmfeatures_updated_at'];

        $missingAttributes = array_diff($osmFeaturesAttributes, $fillable);

        if (!empty($missingAttributes)) {
            throw WmOsmfeaturesException::missingFillables($className, $missingAttributes);
        }

        return true;
    }

    /**
     * Get the class name of the given model
     *
     * @return string
     */
    protected function getClassName(string $modelName)
    {
        if (strpos($modelName, '_') !== false) {
            //split the model name
            $parts = explode('_', $modelName);
            //ucfirst the 2 parts
            return 'App\\Models\\' . ucfirst($parts[0]) . ucfirst($parts[1]);
        }
        return 'App\\Models\\' . $modelName;
    }

    /**
     * Get the table name of the given model
     */
    protected function getTableName(string $className): string
    {
        $instance = new $className;

        $table = $instance->getTable();

        return $table;
    }

    /**
     * Fetch the osmfeatures ids for the given model
     */
    protected function fetchOsmfeaturesIds(string $className): Collection
    {
        $osmfeaturesIds = collect();
        $page = 1;

        do {
            $url = $className::getApiList($page);
            $response = Http::get($url);

            if ($response->failed()) {
                throw WmOsmfeaturesException::invalidUrl($url);
            }

            if ($response->successful() && !empty($response->json()['data'])) {
                $json = $response->json();

                foreach ($json['data'] as $dataItem) {
                    $osmfeaturesIds->push($dataItem['id']);
                }

                $page++;
            } else {
                break;
            }
        } while (!empty($response->json()['data']));

        return $osmfeaturesIds->values();
    }
}
