<?php

namespace VasilGerginski\FilamentShortUrl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VasilGerginski\FilamentShortUrl\FilamentShortUrl
 */
class FilamentShortUrl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \VasilGerginski\FilamentShortUrl\FilamentShortUrl::class;
    }
}
