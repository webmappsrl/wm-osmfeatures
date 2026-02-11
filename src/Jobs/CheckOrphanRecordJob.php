<?php

namespace Wm\WmOsmfeatures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Wm\WmOsmfeatures\Jobs\Abstracts\BaseJob;

class CheckOrphanRecordJob extends BaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function getRedisLockKey(): string
    {
        return 'orphan:' . $this->recordId . ':' . $this->className;
    }

    protected function getLogChannel(): string
    {
        return 'wm-osmfeatures';
    }

    protected $recordId;
    protected $osmfeaturesId;
    protected $className;

    public function __construct($recordId, $osmfeaturesId, $className)
    {
        $this->recordId = $recordId;
        $this->osmfeaturesId = $osmfeaturesId;
        $this->className = $className;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        $record = $this->className::find($this->recordId);

        if (!$record) {
            Log::channel('wm-osmfeatures')->warning("Orphan check: Record {$this->recordId} not found");
            return;
        }

        if (!$record->osmfeatures_id) {
            Log::channel('wm-osmfeatures')->warning("Orphan check: Record {$this->recordId} has no osmfeatures_id");
            return;
        }

        // Se è già marcato come non esistente e non vogliamo ricontrollarlo, skip
        if (isset($record->osmfeatures_exists) && $record->osmfeatures_exists === false) {
            Log::channel('wm-osmfeatures')->debug("Orphan check: Record {$this->recordId} already marked as non-existent, skipping");
            return;
        }

        // Chiama l'API per verificare se esiste ancora
        try {
            $apiUrl = $this->className::getApiSingleFeature($this->osmfeaturesId);
            $response = Http::timeout(10)->get($apiUrl);

            $data = $response->json();

            // Controlla se è "not found"
            $isNotFound = $response->status() === 404
                || ($response->ok() && is_array($data) && ($data['message'] ?? null) === 'Not found');

            if ($isNotFound) {
                // Il record non esiste più su OSMFeatures, aggiorna il flag
                if (method_exists($record, 'isFillable') && $record->isFillable('osmfeatures_exists')) {
                    $record->osmfeatures_exists = false;
                    $record->saveQuietly();

                    Log::channel('wm-osmfeatures')->warning(
                        "Orphan record id {$this->recordId} - Feature {$this->osmfeaturesId} not found on osmfeatures - marking as non-existent"
                    );
                }
            } else {
                // Il record esiste ancora, potrebbe essere un problema di paginazione o altro
                // Aggiorna il flag a true per sicurezza
                if (method_exists($record, 'isFillable') && $record->isFillable('osmfeatures_exists')) {
                    $record->osmfeatures_exists = true;
                    $record->saveQuietly();

                    Log::channel('wm-osmfeatures')->info(
                        "Orphan record id {$this->recordId} - Feature {$this->osmfeaturesId} still exists on osmfeatures - marking as existent"
                    );
                }
            }
        } catch (RequestException $e) {
            // Se l'eccezione contiene un 404, marca come non esistente
            $response = $e->response;
            if ($response && $response->status() === 404) {
                if (method_exists($record, 'isFillable') && $record->isFillable('osmfeatures_exists')) {
                    $record->osmfeatures_exists = false;
                    $record->saveQuietly();

                    Log::channel('wm-osmfeatures')->warning(
                        "Orphan record id {$this->recordId} - Feature {$this->osmfeaturesId} not found on osmfeatures (exception) - marking as non-existent"
                    );
                }
            } else {
                // Per altri errori, rilancia l'eccezione
                throw $e;
            }
        }
    }
}
