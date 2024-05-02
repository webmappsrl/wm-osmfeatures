<?php

namespace Wm\WmOsmfeatures\Exceptions;

class WmOsmfeaturesException extends \Exception
{
    public static function missingEndpoint(): self
    {
        return new self('The endpoint is missing in the model. Please make sure you have implemented the OsmfeaturesSyncableInterface in the model.');
    }

    public static function missingQueryParameters(): self
    {
        return new self('The query parameters are missing in the model. Please make sure you have implemented the OsmfeaturesSyncableInterface in the model.');
    }

    public static function missingModels(): self
    {
        return new self('No models found in App\Models directory of the project.');
    }

    public static function missingInitializedModels(): self
    {
        return new self('No initialized models found in App\Models directory of the project. Make sure you have implemented the OsmfeaturesSyncableTrait and OsmfeaturesSyncableInterface in the model.');
    }

    public static function missingTable(string $table): self
    {
        return new self("Table {$table} not found in database.");
    }

    public static function tableAlreadyInitialized(string $table): self
    {
        return new self("Table {$table} is already initialized with osmfeatures columns.");
    }

    public static function invalidModel(string $className): self
    {
        return new self("Class {$className} is not a valid model.");
    }
}
