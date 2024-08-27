<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Traits\OsmfeaturesCommandHelper;

class WmOsmfeaturesImportSyncCommand extends Command
{
    use OsmfeaturesCommandHelper;

    protected $signature = 'wm-osmfeatures:import-sync';

    protected $description = 'Sync all elements from OSM Features to the local database';

    public function handle()
    {
        $this->info('Starting import sync process...');

        $models = $this->getInitializedModels('Wm\WmOsmfeatures\Traits\OsmfeaturesImportableTrait');

        if (empty($models)) {
            throw WmOsmfeaturesException::missingInitializedModels();
        }

        // Iterate over each model and trigger the import process
        foreach ($models as $modelName) {
            $className = $this->getClassName($modelName);
            $this->info('Starting import for '.$modelName);
            $className::importFromOsmFeatures();
        }

        $this->info('Import sync process completed.');
    }
}
