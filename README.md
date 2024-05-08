## Package Documentation: WmOsmfeatures

### Introduction

The WmOsmfeatures package facilitates synchronization with OpenStreetMap (OSM) features, allowing for seamless integration of OSM data into your Laravel application. This documentation provides an overview of the package's functionality, usage, and integration.

### Features

-   Fetch OSM features data via API
-   Synchronize OSM features with local database
-   Automatic handling of data updates

### Installation

To install the WmOsmfeatures package, follow these steps:

1. Install the package via Composer:

    ```
    composer require webmapp/wm-osmfeatures
    ```

2. Configure your models to use the provided traits and interfaces.

### Usage

#### Setting Up Models

To enable synchronization with OSM features, follow these steps:

1. Implement the `OsmfeaturesSyncableInterface` interface in your model.
2. Use the `OsmfeaturesSyncableTrait` trait in your model.

Example:

```php
use Wm\WmOsmfeatures\Interfaces\OsmfeaturesSyncableInterface;
use Wm\WmOsmfeatures\Traits\OsmfeaturesSyncableTrait;

class Municipality extends Model implements OsmfeaturesSyncableInterface
{
    use OsmfeaturesSyncableTrait;

    // Your model implementation
}
```

#### Configuration

In your model, implement the following methods from the `OsmfeaturesSyncableInterface` interface:

-   `getOsmfeaturesEndpoint`: Returns the OSMFeatures API endpoint.
-   `getOsmfeaturesListQueryParameters`: Returns the query parameters for listing features. Available universal parameters are: updated_at, bbox, score. More informations can be found in the [osmfeatures api documentation](https://osmfeatures.maphub.it/api/documentation)
-   `osmfeaturesUpdateLocalAfterSync`: Updates the local database after a successful OSMFeatures sync.

Example:

```php
class Municipality extends Model implements OsmfeaturesSyncableInterface
{
    // ...

    public static function getOsmfeaturesEndpoint(): string
    {
        return 'https://osmfeatures.maphub.it/api/v1/features/admin-areas/';
    }

    public static function getOsmfeaturesListQueryParameters(): array
    {
        return ['admin_level' => 8];
    }

    public static function osmfeaturesUpdateLocalAfterSync(string $osmfeaturesId): void
    {
        // Your implementation
    }

    // ...
}
```

Also make sure to add osmfeatures columns to fillable attributes in your model.

```
protected $fillable = ['osmfeatures_id', 'osmfeatures_data', 'osmfeatures_updated_at'];
```

#### Synchronization

To synchronize OSM features with your local database, you have two options:

1. **Automatic Synchronization**: Use the provided Artisan command `wm-osmfeatures:sync` to automatically synchronize all initialized models. This command prepares the models and starts the sync process for each one, pushing sync jobs to the queue. 

    ```
    php artisan wm-osmfeatures:sync
    ```

    Alternatively you can provide a ```--model=``` option to perform the sync only for the specified model
   
      ```
    php artisan wm-osmfeatures:sync --model=Municipality
    ```

3. **Manual Import**: Manually import records from OSM features to the local database using the `wm-osmfeatures:import-first` command. This command takes a model and a file path as arguments and dispatches sync jobs for the specified model based on the osmfeatures IDs provided in the file.

    ```
    php artisan wm-osmfeatures:import-first {model} {filepath}
    ```

    The file must be in `.txt` format and should contain a list of osmfeatures IDs, each formatted as follows: `XYYYYY`, where `X` can be `N`, `W`, or `R`, and `Y` is a number greater than 0.

    Example:

    ```
    php artisan wm-osmfeatures:import-first Municipality storage/app/public/osmfeatures.txt
    ```

    Additionally, you can use the `wm-osmfeatures:import-sync` command to manually trigger the sync process for all initialized models. This command iterates over each model and starts the import process fetching     data from osmfeatures API using osmfeatures_id of each model.

    ```
    php artisan wm-osmfeatures:import-sync
    ```

    **Note:** The manual import commands are only available for models that implement the `OsmfeaturesImportableTrait`. Make sure to include the `OsmfeaturesImportableTrait` trait in your model as follows:

    ```php
    use Wm\WmOsmfeatures\Traits\OsmfeaturesImportableTrait;
    use Wm/WmOsmfeatures\Interfaces\OsmfeaturesSyncableInterface;

    class Municipality extends Model implements OsmfeaturesSyncableInterface
    {
        use OsmfeaturesImportableTrait;

        //...
    }
    ```

#### How the OsmfeaturesSyncJob works

The `OsmfeaturesSyncJob` class is responsible for synchronizing OSM features with your local database. It makes a call to the OSM features API and uses the `osmfeaturesUpdateLocalAfterSync(string $osmfeaturesId)` method defined in your model interface to update the local database with the new data. The `OsmfeaturesSyncJob` class runs in the background and synchronizes OSM features with your local database using the methods defined in the `OsmfeaturesSyncableInterface`.

### Conclusion

The WmOsmfeatures package simplifies the integration of OSM features into your Laravel application, providing seamless synchronization and data management capabilities. By following the provided guidelines, you can efficiently incorporate OSM data into your project and leverage its benefits.

For more information and detailed usage instructions, refer to the package documentation and source code.

### Support and Feedback

For support and feedback regarding the WmOsmfeatures package, please contact the package maintainers or open an issue on the GitHub repository. We appreciate any feedback and contributions to improve the package and its functionality.
