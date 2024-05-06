<?php

namespace Wm\WmOsmfeatures\Traits;

use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesTrait
{
    /**
     * Get the osmfeatures detail api url for the model passing the osmfeatures id.
     */
    public static function getApiSingleFeature(string $osmfeatures_id): string
    {
        //check if the model instance has implemented the getOsmfeaturesEndpoint method
        if (! method_exists(static::class, 'getOsmfeaturesEndpoint')) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        //check if osmfeatures_id is in the correct format
        if (! preg_match('/^[NWR][1-9]\d*$/', $osmfeatures_id)) {
            throw WmOsmfeaturesException::invalidOsmfeaturesId($osmfeatures_id);
        }

        $endpoint = static::getOsmfeaturesEndpoint();

        //if endpoint does not finish with '/', add it
        if (substr($endpoint, -1) != '/') {
            $endpoint .= '/';
        }

        return $endpoint.$osmfeatures_id;
    }
}
