<?php

declare(strict_types=1);

namespace VasilGerginski\FilamentShortUrl\Models;

use AshAllenDesign\ShortURL\Models\ShortURL as BaseShortURL;

/**
 * Extended ShortURL model with marketing fields.
 *
 * @property string|null $description
 * @property float|null $price
 * @property string $currency
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 */
class ShortUrl extends BaseShortURL
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // Original fields
        'destination_url',
        'default_short_url',
        'url_key',
        'single_use',
        'forward_query_params',
        'track_visits',
        'redirect_status_code',
        'track_ip_address',
        'track_operating_system',
        'track_operating_system_version',
        'track_browser',
        'track_browser_version',
        'track_referer_url',
        'track_device_type',
        'activated_at',
        'deactivated_at',
        // Marketing fields
        'description',
        'price',
        'currency',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'single_use' => 'boolean',
        'forward_query_params' => 'boolean',
        'track_visits' => 'boolean',
        'track_ip_address' => 'boolean',
        'track_operating_system' => 'boolean',
        'track_operating_system_version' => 'boolean',
        'track_browser' => 'boolean',
        'track_browser_version' => 'boolean',
        'track_referer_url' => 'boolean',
        'track_device_type' => 'boolean',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'price' => 'decimal:2',
    ];
}
