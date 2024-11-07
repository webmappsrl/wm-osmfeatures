<?php

namespace Wm\WmOsmfeatures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;
use Wm\WmPackage\Jobs\Abstract\BaseJob;

class OsmfeaturesSyncJob extends BaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function getRedisLockKey(): string
    {
        return $this->osmfeaturesId.':'.$this->className;
    }

    protected function getLogChannel(): string
    {
        return 'wm-osmfeatures';
    }

    protected $osmfeaturesId;

    protected $className; //commento

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
        $singleFeatureApi = $this->className::getApiSingleFeature($this->osmfeaturesId);

        $dataToRetrieve = [];

        $response = Http::get($singleFeatureApi);

        if ($response->failed() || $response->json() === null) {
            throw WmOsmfeaturesException::invalidUrl($singleFeatureApi);
        }

        $data = $response->json();
        $dataToRetrieve['osmfeatures_id'] = $data['properties']['osmfeatures_id'];
        $dataToRetrieve['osmfeatures_data'] = $data;
        $dataToRetrieve['osmfeatures_updated_at'] = $data['properties']['updated_at'];

        $this->className::updateOrCreate(['osmfeatures_id' => $data['properties']['osmfeatures_id']], $dataToRetrieve);
        $this->className::osmfeaturesUpdateLocalAfterSync($this->osmfeaturesId);
    }
}
