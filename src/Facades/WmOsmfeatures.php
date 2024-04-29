<?php

namespace Wm\WmOsmfeatures\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wm\WmOsmfeatures\WmOsmfeatures
 */
class WmOsmfeatures extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wm\WmOsmfeatures\WmOsmfeatures::class;
    }
}
