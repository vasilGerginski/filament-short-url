<?php

namespace VasilGerginski\FilamentShortUrl\Commands;

use Illuminate\Console\Command;

class FilamentShortUrlCommand extends Command
{
    public $signature = 'filament-short-url';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
