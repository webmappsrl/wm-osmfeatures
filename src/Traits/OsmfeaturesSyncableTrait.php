<?php

namespace Wm\WmOsmfeatures\Traits;

use Wm\WmOsmfeatures\Exceptions\WmOsmfeaturesException;

trait OsmfeaturesSyncableTrait
{
    /**
     * Get the data from osmfeatures list api url for the model with the given parameters.
     *
     * @throws WmOsmfeaturesException
     */
    public function getApiList(?string $updated_at = null, int $page = 1, ?string $bbox = null, ?int $score = null, ?int $admin_level = null): string
    {
        $apiUrl = $this->osmfeatures_endpoint;

        if (! $apiUrl) {
            throw WmOsmfeaturesException::missingEndpoint();
        }

        //build the query
        $query = [
            'page' => $page,
        ];

        if ($updated_at) {
            $query['updated_at'] = $updated_at;
        }

        if ($bbox) {
            $query['bbox'] = $bbox;
        }

        if ($score) {
            $query['score'] = $score;
        }

        if ($admin_level) {
            $query['admin_level'] = $admin_level;
        }

        $apiUrl .= '?'.http_build_query($query);

        return $apiUrl;
    }
}
