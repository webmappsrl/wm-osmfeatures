<?php

namespace Wm\WmOsmfeatures\Traits;

use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesSyncableTrait
{
    /**
     * Get the data from osmfeatures list api url for the model with the given page parameter.
     * @param int $page
     * @throws WmOsmfeaturesException
     */
    public function getApiList(int $page = 1): string
    {
        //check if the model instance has implemented the getOsmfeaturesListQueryParameters method
        if (!method_exists($this, 'getOsmfeaturesListQueryParameters')) {
            throw WmOsmfeaturesException::missingQueryParameters();
        }

        //check if the model instance has implemented the getOsmfeaturesEndpoint method
        if (!method_exists($this, 'getOsmfeaturesEndpoint')) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        $endpoint = $this->getOsmfeaturesEndpoint();
        $queryParameters = $this->getOsmfeaturesListQueryParameters($page);
        $queryParameters['page'] = $page;

        return $endpoint . '?' . http_build_query($queryParameters);
    }

    /**
     * Get the osmfeatures detail api url for the model passing the osmfeatures id.
     * 
     * @param string $osmfeatures_id
     * @return string
     */
    public function getApiSingleFeature(string $osmfeatures_id): string
    {
        if (!$this->osmfeatures_endpoint) {
            throw WmOsmfeaturesException::missingEndpoint();
        }
        return $this->osmfeatures_endpoint . '/' . $osmfeatures_id;
    }
}
