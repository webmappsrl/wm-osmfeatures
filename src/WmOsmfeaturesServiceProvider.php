<?php

namespace Wm\WmOsmfeatures;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Wm\WmOsmfeatures\Commands\WmOsmfeaturesCommand;

class WmOsmfeaturesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('wm-osmfeatures')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_wm-osmfeatures_table')
            ->hasCommand(WmOsmfeaturesCommand::class);
    }
}
