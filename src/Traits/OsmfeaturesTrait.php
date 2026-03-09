<?php

namespace Wm\WmOsmfeatures\Traits;

use Illuminate\Support\Facades\Http;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesTrait
{
    /**
     * Get the osmfeatures detail api url for the model passing the osmfeatures id.
     */
    public static function getApiSingleFeature(string $osmfeatures_id): string
    {
        // check if the model instance has implemented the getOsmfeaturesEndpoint method
        if (! method_exists(static::class, 'getOsmfeaturesEndpoint')) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        // check if osmfeatures_id is in the correct format
        if (! preg_match('/^[NWR][1-9]\d*$/', $osmfeatures_id)) {
            throw WmOsmfeaturesException::invalidOsmfeaturesId($osmfeatures_id);
        }

        $endpoint = static::getOsmfeaturesEndpoint();

        // if endpoint does not finish with '/', add it
        if (substr($endpoint, -1) != '/') {
            $endpoint .= '/';
        }

        return $endpoint.$osmfeatures_id;
    }

    /**
     * Refresh a single feature by fetching it directly from OSM via the osmfeatures refresh endpoint.
     * This bypasses the Lua sync pipeline and updates the local record immediately.
     *
     * @throws WmOsmfeaturesException
     */
    public static function refreshSingleFeatureFromOsm(string $osmfeaturesId): void
    {
        $baseUrl = rtrim(static::getOsmfeaturesBaseUrl(), '/');
        $url = $baseUrl.'/api/v2/features/refresh/'.$osmfeaturesId;

        $response = Http::timeout(30)->get($url);

        if ($response->status() === 404) {
            throw WmOsmfeaturesException::modelNotFound($osmfeaturesId);
        }

        if ($response->failed() || $response->json() === null) {
            throw WmOsmfeaturesException::invalidUrl($url);
        }

        $data = $response->json();

        if (! isset($data['properties']['osmfeatures_id'])) {
            throw WmOsmfeaturesException::invalidUrl($url);
        }

        $dataToRetrieve = [
            'osmfeatures_id' => $data['properties']['osmfeatures_id'],
            'osmfeatures_data' => $data,
            'osmfeatures_updated_at' => $data['properties']['updated_at'] ?? now(),
        ];

        static::updateOrCreate(['osmfeatures_id' => $osmfeaturesId], $dataToRetrieve);
        static::osmfeaturesUpdateLocalAfterSync($osmfeaturesId);
    }

    /**
     * Extract the base URL (scheme + host) from the osmfeatures endpoint.
     * e.g. "https://osmfeatures.maphub.it/api/v1/features/poles/" → "https://osmfeatures.maphub.it"
     */
    protected static function getOsmfeaturesBaseUrl(): string
    {
        $endpoint = static::getOsmfeaturesEndpoint();
        $parsed = parse_url($endpoint);

        return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
    }
}
