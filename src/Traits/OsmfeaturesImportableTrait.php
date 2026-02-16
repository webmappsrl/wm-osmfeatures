<?php

namespace Wm\WmOsmfeatures\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesImportableTrait
{
    use OsmfeaturesTrait;

    /**
     * Update local database records based on osmfeatures_id.
     *
     * @throws WmOsmfeaturesException
     */
    public static function importFromOsmFeatures(): void
    {
        $osmfeaturesIds = static::pluck('osmfeatures_id')->toArray();

        foreach ($osmfeaturesIds as $osmfeaturesId) {
            if ($osmfeaturesId === null) {
                continue;
            }
            try {
                static::importSingleFeature($osmfeaturesId);
            } catch (WmOsmfeaturesException $exception) {
                Log::error("Error importing osmfeature with ID $osmfeaturesId: ".$exception->getMessage());
            }
        }
    }

    /**
     * Update a single local record based on osmfeatures_id.
     *
     * @throws WmOsmfeaturesException
     */
    public static function importSingleFeature(string $osmfeaturesId): void
    {
        // Check if the model has osm2cai_status field and if it's validated (status 4) - if so, skip import
        $existingRecord = static::where('osmfeatures_id', $osmfeaturesId)->first();
        if ($existingRecord && isset($existingRecord->osm2cai_status) && $existingRecord->osm2cai_status > 3) {
            $id = $existingRecord->id;
            Log::channel('wm-osmfeatures')->info("id {$id} - Record {$osmfeaturesId} is validated (status 4) - skipping import to preserve validated data");

            return;
        }

        $singleFeatureApi = static::getApiSingleFeature($osmfeaturesId);
        $response = Http::get($singleFeatureApi);

        if ($response->failed() || $response->json() === null) {
            throw WmOsmfeaturesException::invalidUrl($singleFeatureApi);
        }

        $data = $response->json();
        $dataToRetrieve = [
            'osmfeatures_id' => $data['properties']['osmfeatures_id'],
            'osmfeatures_data' => $data,
            'osmfeatures_updated_at' => $data['properties']['updated_at'],
        ];

        static::updateOrCreate(['osmfeatures_id' => $osmfeaturesId], $dataToRetrieve);
        static::osmfeaturesUpdateLocalAfterSync($osmfeaturesId);
    }
}
