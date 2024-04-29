<?php

namespace Wm\WmOsmfeatures\Commands;

use Illuminate\Console\Command;

class WmOsmfeaturesCommand extends Command
{
    public $signature = 'wm-osmfeatures';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
