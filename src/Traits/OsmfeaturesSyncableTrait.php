<?php

namespace Wm\WmOsmfeatures\Traits;

use Wm\WmOsmfeatures\Traits\OsmfeaturesTrait;
use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesSyncableTrait
{
    use OsmfeaturesTrait;
    /**
     * Get the osmfeatures list api url with the query parameters.
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
}
