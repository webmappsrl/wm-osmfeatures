<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
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
            $this->info('Fetching ids for '.$model);
            $osmfeaturesIds = $this->fetchOsmfeaturesIds($className);
            if ($osmfeaturesIds->isEmpty()) {
                throw WmOsmfeaturesException::noOsmfeaturesIdsFound($className);
            }

            $this->info('Fetched '.count($osmfeaturesIds).' ids');
            $this->info('Dispatching jobs for '.$model);

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
                $this->info('Initializing table for '.$modelName);

                $className = $this->getClassName($modelName);
                $table = $this->getTableName($className);

                Artisan::call('wm-osmfeatures:initialize-tables', ['--table' => $table]);
                $this->checkFillables($className);

                $this->info('Fetching ids for '.$modelName);

                $osmfeaturesIds = $this->fetchOsmfeaturesIds($className);
                if ($osmfeaturesIds->isEmpty()) {
                    throw WmOsmfeaturesException::noOsmfeaturesIdsFound($modelName);
                }

                $this->info('Fetched '.count($osmfeaturesIds).' ids');
                $this->info('Dispatching jobs for '.$modelName);

                // dispatch a job for each osmfeatures id
                $osmfeaturesIds->each(function ($osmfeaturesId) use ($className) {
                    dispatch(new OsmfeaturesSyncJob($osmfeaturesId, $className));
                });
                $this->info("Jobs pushed for $modelName");
                Log::info("Jobs pushed for $modelName");
            }
        }
    }
}
