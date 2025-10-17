<?php

namespace Wm\WmOsmfeatures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmOsmfeatures\Jobs\Abstracts\BaseJob;

class OsmfeaturesSyncJob extends BaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function getRedisLockKey(): string
    {
        return $this->osmfeaturesId . ':' . $this->className;
    }

    protected function getLogChannel(): string
    {
        return 'wm-osmfeatures';
    }

    protected $osmfeaturesId;

    protected $className;

    public function __construct($osmfeaturesId, $className)
    {
        $this->className = $className;
        $this->osmfeaturesId = $osmfeaturesId;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        // Check if the model has osm2cai_status field and if it's validated (status 4) - if so, skip sync
        $existingRecord = $this->className::where('osmfeatures_id', $this->osmfeaturesId)->first();
        if ($existingRecord && isset($existingRecord->osm2cai_status) && $existingRecord->osm2cai_status > 3) {
            $id = $existingRecord->id;
            Log::channel('wm-osmfeatures')->info("id {$id} - Record {$this->osmfeaturesId} is validated (status {$existingRecord->osm2cai_status}) - skipping sync to preserve validated data");

            return;
        }

        $singleFeatureApi = $this->className::getApiSingleFeature($this->osmfeaturesId);

        $dataToRetrieve = [];

        $response = Http::get($singleFeatureApi);

        if ($response->failed() || $response->json() === null) {
            throw WmOsmfeaturesException::invalidUrl($singleFeatureApi);
        }

        $data = $response->json();
        $dataToRetrieve['osmfeatures_id'] = $this->osmfeaturesId;
        $dataToRetrieve['osmfeatures_data'] = $data;
        $dataToRetrieve['osmfeatures_updated_at'] = $data['properties']['updated_at'];
        if (method_exists($this->className, 'extractPropertiesFromOsmfeatures')) {
            $dataToRetrieve['properties'] = $this->extractProperties($data);
        }

        $this->className::updateOrCreate(['osmfeatures_id' => $this->osmfeaturesId], $dataToRetrieve);
        $this->className::osmfeaturesUpdateLocalAfterSync($this->osmfeaturesId);
    }

    private function extractProperties($data)
    {
        $existingRecord = $this->className::where('osmfeatures_id', $this->osmfeaturesId)->first();
        $existingProps = $existingRecord ? $existingRecord->properties : [];
        $modelId = $existingRecord ? $existingRecord->id : null;

        $extractedProps = $this->className::extractPropertiesFromOsmfeatures($data, $modelId);
        // if existingprops is not an array, or it is empty, return extractedprops
        if (! is_array($existingProps) || empty($existingProps)) {
            return $extractedProps;
        }

        foreach ($extractedProps as $key => $value) {
            if (array_key_exists($key, $existingProps)) {
                $existingProps[$key] = $value;
            }
        }

        return $existingProps;
    }
}
