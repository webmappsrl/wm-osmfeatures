# Package documentation: wm-osmfeatures

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webmapp/wm-osmfeatures.svg?style=flat-square)](https://packagist.org/packages/webmapp/wm-osmfeatures)
[![Total Downloads](https://img.shields.io/packagist/dt/webmapp/wm-osmfeatures.svg?style=flat-square)](https://packagist.org/packages/webmapp/wm-osmfeatures)

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
    composer require wm/wmosmfeatures
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

-   `getOsmfeaturesEndpoint`: Returns the OSMFeatures API endpoint for listing features.
-   `getOsmfeaturesListQueryParameters`: Returns the query parameters for listing features.
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

To synchronize OSM features with your local database, use the provided Artisan command:

```
php artisan wm-osmfeatures:sync
```

This command will prepare the initialized models table to synchronize OSM features with your local database. It then
starts the sync process for all initialized models and pushes sync jobs to the queue.

#### How the OsmfeaturesSyncJob works

The `OsmfeaturesSyncJob` class is responsible for synchronizing OSM features with your local database. It makes a call to osmfeatures API and uses the `osmfeaturesUpdateLocalAfterSync(string $osmfeaturesId)` method defined in your model interface to update the local database with the new data. The `OsmfeaturesSyncJob` class runs in the background and synchronizes OSM features with your local database using the `OsmfeaturesSyncableInterface` methods.

### Conclusion

The WmOsmfeatures package simplifies the integration of OSM features into your Laravel application, providing seamless synchronization and data management capabilities. By following the provided guidelines, you can efficiently incorporate OSM data into your project and leverage its benefits.

For more information and detailed usage instructions, refer to the package documentation and source code.

### Support and Feedback

For support and feedback regarding the WmOsmfeatures package, please contact the package maintainers or open an issue on the GitHub repository. We appreciate any feedback and contributions to improve the package and its functionality.

---


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Webmapp Srl](https://github.com/webmappsrl)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
