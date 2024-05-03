<?php

namespace Wm\WmOsmfeatures\Interfaces;

interface OsmfeaturesSyncableInterface
{
    /**
     * Returns the OSMFeatures API endpoint for listing features for the model.
     */
    public static function getOsmfeaturesEndpoint(): string;

    /**
     * Returns the query parameters for listing features for the model.
     *
     * The array keys should be the query parameter name and the values
     * should be the expected value.
     *
     * @return array<string,string>
     */
    public static function getOsmfeaturesListQueryParameters(): array;

    /**
     * Update the local database after a successful OSMFeatures sync.
     */
    public static function osmfeaturesUpdateLocalAfterSync(string $osmfeaturesId): void;
}