<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Jobs\OsmfeaturesSyncJob;
use Wm\WmOsmfeatures\Traits\OsmfeaturesCommandHelper;
use Wm\WmOsmfeatures\Traits\OsmfeaturesImportableTrait;

class WmOsmfeaturesImportFirst extends Command
{
    use OsmfeaturesCommandHelper;

    public $signature = 'wm-osmfeatures:import-first {model} {filepath}';

    public $description = 'Dispatch sync jobs for the initialized models with osmfeatures_id included in the provided file. File must be in .txt format and should contain a list of osmfeatures ids each formatted as follow: XYYYYY where X can be N,W,R and Y is a number greather than 0';

    public function handle()
    {
        $this->info('Checking initialized model...');
        //check if the provided model class use the OsmfeaturesImportableTrait
        $className = $this->getClassName($this->argument('model'));

        if (! class_exists($className)) {
            throw WmOsmfeaturesException::invalidModel($className);
        }

        if (! in_array(OsmfeaturesImportableTrait::class, class_uses_recursive($className))) {
            throw WmOsmfeaturesException::missingInitializedModels();
        }

        $this->checkFillables($className);
        $this->info('Initializing table for '.$className);

        $table = $this->getTableName($className);
        Artisan::call('wm-osmfeatures:initialize-tables', ['--table' => $table]);

        $this->info('Table initialized');

        $this->info($className.' is ready for the import');

        //validate the file
        $this->info('Validating file '.$this->argument('filepath'));
        $this->validateFile($this->argument('filepath'));

        //get the osmfeatures ids from the file
        $this->info('Getting osmfeatures ids from '.$this->argument('filepath'));
        $osmfeaturesIds = $this->getOsmfeatureIdsFromFile($this->argument('filepath'));
        $this->info('Found '.count($osmfeaturesIds).' ids in '.$this->argument('filepath'));

        //dispatch sync jobs for every osmfeatures_id
        $this->info('Dispatching jobs for '.$className);
        foreach ($osmfeaturesIds as $osmfeaturesId) {
            dispatch(new OsmfeaturesSyncJob($osmfeaturesId, $className));
        }
        $this->info('Jobs dispatched');
    }

    /**
     * Validate the file
     *
     * @return void
     *
     * @throws WmOsmfeaturesException
     */
    protected function validateFile(string $filepath)
    {
        //check if the file exists
        if (! file_exists($filepath)) {
            throw WmOsmfeaturesException::invalidFile($filepath);
        }
        //file should have .txt extension
        if (pathinfo($filepath, PATHINFO_EXTENSION) !== 'txt') {
            throw WmOsmfeaturesException::invalidFileExtension($filepath);
        }
        //get the file content
        $fileContent = file_get_contents($filepath);

        //file content should contain a list of osmfeatures_ids each formatted as follow: XYYYYY where X can be N,W,R and Y is a number greather than 0 and put one per line
        if (! preg_match_all('/^[NWR][1-9]\d*$/m', $fileContent, $matches)) {
            throw WmOsmfeaturesException::invalidFile($filepath);
        }
    }

    /**
     * Get osmfeatures ids from the file
     */
    protected function getOsmfeatureIdsFromFile(string $filepath): array
    {
        $fileContent = file_get_contents($filepath);

        return explode(PHP_EOL, $fileContent);
    }
}
