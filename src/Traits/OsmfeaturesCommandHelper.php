<?php

namespace Wm\WmOsmfeatures\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesCommandHelper
{
    /**
     * Return an array of the model's names that implement the OsmfeaturesSyncableTrait
     */
    protected function getInitializedModels(string $trait): array
    {
        $modelDirectory = app_path('/Models');

        // get all the files in the model directory
        $modelFiles = File::files($modelDirectory);

        if (empty($modelFiles)) {
            throw WmOsmfeaturesException::missingModels();
        }

        $models = [];

        foreach ($modelFiles as $file) {
            // get the file content
            $content = File::get($file);

            // check if the content contains the trait
            if (Str::contains($content, $trait)) {
                // get the model name
                $model = Str::before($file->getFilename(), '.php');

                $models[] = $model;
            }
        }

        return $models;
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

        if (! empty($missingAttributes)) {
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
            // split the model name
            $parts = explode('_', $modelName);

            // Filter out empty parts and check if we have at least 2 non-empty parts
            $nonEmptyParts = array_filter($parts, function ($part) {
                return ! empty(trim($part));
            });

            if (count($nonEmptyParts) < 2) {
                throw new \InvalidArgumentException("Model name '{$modelName}' with underscore must have at least 2 non-empty parts");
            }

            // ucfirst the first 2 non-empty parts
            $nonEmptyParts = array_values($nonEmptyParts); // Re-index array

            return 'App\\Models\\'.ucfirst($nonEmptyParts[0]).ucfirst($nonEmptyParts[1]);
        }

        return 'App\\Models\\'.ucfirst($modelName);
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
            // Aggiungo timeout e retry per gestire timeout transitori
            $response = Http::timeout(30)
                ->retry(3, 2000) // 3 tentativi, 2s di attesa tra uno e l'altro
                ->get($url);

            if ($response->failed()) {
                throw WmOsmfeaturesException::invalidUrl($url);
            }

            if ($response->successful() && ! empty($response->json()['data'])) {
                $json = $response->json();

                foreach ($json['data'] as $dataItem) {
                    $osmfeaturesIds->push($dataItem['id']);
                }

                $page++;
            } else {
                break;
            }
        } while (! empty($response->json()['data']));

        return $osmfeaturesIds->values();
    }
}
