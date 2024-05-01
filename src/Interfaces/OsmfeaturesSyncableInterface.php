<?php

namespace Wm\WmOsmfeatures\Interfaces;

interface OsmfeaturesSyncableInterface
{
    /**
     * Returns the OSMFeatures API endpoint for listing features for the model.
     */
    public function getOsmfeaturesEndpoint(): string;

    /**
     * Returns the query parameters for listing features for the model.
     *
     * The array keys should be the query parameter name and the values
     * should be the expected value.
     *
     * @return array<string,string>
     */
    public function getOsmfeaturesListQueryParameters(): array;
}
