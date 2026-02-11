<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Jobs\CheckOrphanRecordJob;
use Wm\WmOsmfeatures\Jobs\OsmfeaturesSyncJob;
use Wm\WmOsmfeatures\Traits\OsmfeaturesCommandHelper;

class WmOsmfeaturesCommand extends Command
{
    use OsmfeaturesCommandHelper;

    public $signature = 'wm-osmfeatures:sync {--model=}';

    public $description = 'Begin the OSMFeaturessync process for the initialized models and push sync jobs to the queue.';

    public function handle()
    {
        if ($this->option('model')) {
            $model = $this->option('model');
            $className = $this->getClassName($model);
            $table = $this->getTableName($className);

            Artisan::call('wm-osmfeatures:initialize-tables', ['--table' => $table]);
            $this->checkFillables($className);
            $this->info('Fetching ids for ' . $model);
            $osmfeaturesIds = $this->fetchOsmfeaturesIds($className);
            if ($osmfeaturesIds->isEmpty()) {
                throw WmOsmfeaturesException::noOsmfeaturesIdsFound($className);
            }

            $this->info('Fetched ' . count($osmfeaturesIds) . ' ids');

            // Trova e dispatcha job per i record "orfani" nel DB che non sono più presenti in OSMFeatures
            $orphanCount = $this->dispatchOrphanRecordJobs($className, $osmfeaturesIds);
            if ($orphanCount > 0) {
                $this->info("Dispatched {$orphanCount} orphan record check jobs");
            }

            $this->info('Dispatching sync jobs for ' . $model);
            
            // dispatch a job for each osmfeatures id
            $osmfeaturesIds->each(function ($osmfeaturesId) use ($className) {
                dispatch(new OsmfeaturesSyncJob($osmfeaturesId, $className));
            });
            $this->info("Jobs pushed for $model");
            Log::info("Jobs pushed for $model");
        } else {
            $this->info('Checking initialized models...');
            $models = $this->getInitializedModels('Wm\WmOsmfeatures\Traits\OsmfeaturesSyncableTrait');

            if (empty($models)) {
                throw WmOsmfeaturesException::missingInitializedModels();
            }

            // for each model initialized with the trait, initialize the table and get all the instances
            foreach ($models as $modelName) {
                $this->info('Initializing table for ' . $modelName);

                $className = $this->getClassName($modelName);
                $table = $this->getTableName($className);

                Artisan::call('wm-osmfeatures:initialize-tables', ['--table' => $table]);
                $this->checkFillables($className);

                $this->info('Fetching ids for ' . $modelName);

                $osmfeaturesIds = $this->fetchOsmfeaturesIds($className);
                if ($osmfeaturesIds->isEmpty()) {
                    throw WmOsmfeaturesException::noOsmfeaturesIdsFound($modelName);
                }

                $this->info('Fetched ' . count($osmfeaturesIds) . ' ids');

                // Trova e dispatcha job per i record "orfani" nel DB che non sono più presenti in OSMFeatures
                $orphanCount = $this->dispatchOrphanRecordJobs($className, $osmfeaturesIds);
                if ($orphanCount > 0) {
                    $this->info("Dispatched {$orphanCount} orphan record check jobs");
                }

                $this->info('Dispatching sync jobs for ' . $modelName);
                
                // dispatch a job for each osmfeatures id
                $osmfeaturesIds->each(function ($osmfeaturesId) use ($className) {
                    dispatch(new OsmfeaturesSyncJob($osmfeaturesId, $className));
                });
                $this->info("Jobs pushed for $modelName");
                Log::info("Jobs pushed for $modelName");
            }
        }
    }

    /**
     * Trova i record "orfani" nel DB che non sono più presenti nella lista di OSMFeatures
     * e dispatcha un job per ciascuno per verificare se esistono ancora
     */
    protected function dispatchOrphanRecordJobs(string $className, $osmfeaturesIds): int
    {
        // Trova tutti i record nel DB che hanno un osmfeatures_id
        $allDbRecords = $className::whereNotNull('osmfeatures_id')
            ->where('osmfeatures_id', '!=', '')
            ->get();

        if ($allDbRecords->isEmpty()) {
            return 0;
        }

        // Converti la collection di ID da OSMFeatures in array per il confronto
        $osmfeaturesIdsArray = $osmfeaturesIds->toArray();

        // Trova i record "orfani" (presenti nel DB ma non nella lista di OSMFeatures)
        $orphanRecords = $allDbRecords->filter(function ($record) use ($osmfeaturesIdsArray) {
            return !in_array($record->osmfeatures_id, $osmfeaturesIdsArray);
        });

        if ($orphanRecords->isEmpty()) {
            return 0;
        }

        $this->info('Found ' . $orphanRecords->count() . ' orphan records, dispatching check jobs...');

        // Dispatcha un job per ogni record orfano
        $orphanRecords->each(function ($record) use ($className) {
            dispatch(new CheckOrphanRecordJob($record->id, $record->osmfeatures_id, $className));
        });

        return $orphanRecords->count();
    }
}
