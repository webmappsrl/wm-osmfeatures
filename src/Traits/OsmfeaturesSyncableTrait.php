<?php

namespace Wm\WmOsmfeatures\Traits;

use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesSyncableTrait
{
    /**
     * Get the data from osmfeatures list api url for the model with the given page parameter.
     *
     * @throws WmOsmfeaturesException
     */
    public static function getApiList(int $page = 1): string
    {
        //check if the model instance has implemented the getOsmfeaturesListQueryParameters method
        if (!method_exists(__CLASS__, 'getOsmfeaturesListQueryParameters')) {
            throw WmOsmfeaturesException::missingQueryParameters();
        }

        //check if the model instance has implemented the getOsmfeaturesEndpoint method
        if (!method_exists(__CLASS__, 'getOsmfeaturesEndpoint')) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        $endpoint = static::getOsmfeaturesEndpoint();

        //if endpoint does not finish with '/', add it
        if (substr($endpoint, -1) != '/') {
            $endpoint .= '/';
        }

        $queryParameters = static::getOsmfeaturesListQueryParameters($page);
        $queryParameters['page'] = $page;

        return $endpoint . 'list?' . http_build_query($queryParameters);
    }

    /**
     * Get the osmfeatures detail api url for the model passing the osmfeatures id.
     */
    public static function getApiSingleFeature(string $osmfeatures_id): string
    {
        //check if the model instance has implemented the getOsmfeaturesEndpoint method
        if (!method_exists(__CLASS__, 'getOsmfeaturesEndpoint')) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        //check if osmfeatures_id is in the correct format
        if (!preg_match('/^[NWR][1-9]\d*$/', $osmfeatures_id)) {
            throw WmOsmfeaturesException::invalidOsmfeaturesId($osmfeatures_id);
        }

        $endpoint = static::getOsmfeaturesEndpoint();

        //if endpoint does not finish with '/', add it
        if (substr($endpoint, -1) != '/') {
            $endpoint .= '/';
        }

        return $endpoint . $osmfeatures_id;
    }
}