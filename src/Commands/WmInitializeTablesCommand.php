<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Traits\OsmfeaturesCommandHelper;

class WmInitializeTablesCommand extends Command
{
    use OsmfeaturesCommandHelper;

    protected $signature = 'wm-osmfeatures:initialize-tables {--table=}';

    protected $description = 'Initialize tables for OSMFeatures sync process';

    public function handle()
    {
        if (! $this->option('table')) {
            $this->info('Checking initialized models...');
            $models = $this->getInitializedModels('Wm\WmOsmfeatures\Traits\OsmfeaturesSyncableTrait');

            if (empty($models)) {
                throw WmOsmfeaturesException::missingInitializedModels();
            }

            foreach ($models as $modelName) {
                $this->info('Initializing table for '.$modelName);

                $className = $this->getClassName($modelName);
                $table = $this->getTableName($className);
                $this->initializeTable($table);
            }
        } else {
            $table = $this->option('table');
            $this->initializeTable($table);
        }
    }

    protected function initializeTable(string $table)
    {
        $schema = DB::getSchemaBuilder();

        if (! $schema->hasTable($table)) {
            throw WmOsmfeaturesException::missingTable($table);
        }

        if ($schema->hasColumns($table, ['osmfeatures_id', 'osmfeatures_data', 'osmfeatures_updated_at'])) {
            Log::info("Table $table already initialized");
            $this->info("Table $table already initialized, skipping");

            return;
        }

        if (! in_array('osmfeatures_id', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_id varchar(255)");
        }

        if (! in_array('osmfeatures_data', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_data jsonb");
        }

        if (! in_array('osmfeatures_updated_at', $schema->getColumnListing($table))) {
            DB::statement("ALTER TABLE $table ADD COLUMN osmfeatures_updated_at timestamp");
        }

        $this->info("Table $table initialized for the osmfeatures sync");
    }
}
