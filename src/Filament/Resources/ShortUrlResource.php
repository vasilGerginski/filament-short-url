<?php

namespace VasilGerginski\FilamentShortUrl\Filament\Resources;

use VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;
use VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStats;
use VasilGerginski\FilamentShortUrl\Models\ShortUrl;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ShortUrlResource extends Resource
{
    protected static ?string $modelLabel = 'Short URL';

    protected static ?string $pluralModelLabel = 'Short URLs';

    protected static ?string $navigationBadgeColor = 'primary';

    protected static ?string $model = ShortUrl::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static null|SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function isScopedToTenant(): bool
    {
        return config('filament-short-url.tenant_scope', false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('URL Information')
                    ->description('Create and manage your short links')
                    ->schema([
                        TextInput::make('destination_url')
                            ->label('Destination URL')
                            ->placeholder('https://example.com/long-url')
                            ->helperText('The original URL to be shortened'),

                        TextInput::make('default_short_url')
                            ->label('Short URL')
                            ->helperText('Click to copy'),

                        TextInput::make('url_key')
                            ->label('URL Key')
                            ->helperText('Unique identifier'),
                    ]),

                Section::make('Tracking Settings')
                    ->schema([
                        ToggleButtons::make('tracking_level')
                            ->label('Tracking Level')
                            ->options([
                                'basic' => 'Basic',
                                'advanced' => 'Advanced',
                                'none' => 'None',
                            ]),

                        Fieldset::make('Advanced Options')
                            ->schema([
                                TextInput::make('destination_url')
                                    ->required()
                                    ->live()
                                    ->maxLength(255)
                                    ->url()
                                    ->columnSpan(['lg' => 3, 'xs' => 6]),
                                TextInput::make('default_short_url')
                                    ->readOnly()
                                    ->maxLength(255)
                                    ->columnSpan(['xl' => 2, 'xs' => 6]),
                                TextInput::make('url_key')
                                    ->readOnly()
                                    ->maxLength(255)
                                    ->columnSpan(['xl' => 1, 'xs' => 6]),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),

                        // Description field
                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter a short description to help identify this URL')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // Price and Currency fields
                        Group::make()
                            ->schema([
                                TextInput::make('price')
                                    ->label('Campaign Cost')
                                    ->placeholder('0.00')
                                    ->numeric()
                                    ->step(0.01)
                                    ->helperText('Enter the cost of this campaign/URL for ROI tracking')
                                    ->columnSpan(2),
                                Select::make('currency')
                                    ->label('Currency')
                                    ->options([
                                        'USD' => 'USD ($)',
                                        'EUR' => 'EUR (€)',
                                        'GBP' => 'GBP (£)',
                                        'BGN' => 'BGN (лв)',
                                        'JPY' => 'JPY (¥)',
                                        'CAD' => 'CAD (C$)',
                                        'AUD' => 'AUD (A$)',
                                    ])
                                    ->default('USD')
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),

                        // UTM Parameters Section
                        Section::make('UTM Parameters')
                            ->description('Add UTM parameters to track campaign performance. These will be automatically appended to the destination URL.')
                            ->schema([
                                TextInput::make('utm_source')
                                    ->label('UTM Source')
                                    ->placeholder('google, facebook, newsletter')
                                    ->helperText('Identifies the source (e.g., google, facebook, newsletter)')
                                    ->columnSpan(1),

                                TextInput::make('utm_medium')
                                    ->label('UTM Medium')
                                    ->placeholder('cpc, social, email')
                                    ->helperText('Identifies the medium (e.g., cpc, social, email)')
                                    ->columnSpan(1),

                                TextInput::make('utm_campaign')
                                    ->label('UTM Campaign')
                                    ->placeholder('spring_sale, awareness_campaign')
                                    ->helperText('Identifies the campaign name')
                                    ->columnSpan(2),

                                TextInput::make('utm_term')
                                    ->label('UTM Term')
                                    ->placeholder('keyword, audience_segment')
                                    ->helperText('Identifies keywords or audience segments')
                                    ->columnSpan(1),

                                TextInput::make('utm_content')
                                    ->label('UTM Content')
                                    ->placeholder('ad_variant_a, banner_top')
                                    ->helperText('Identifies ad variant or content')
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->collapsed()
                            ->columnSpanFull(),

                        Group::make()
                            ->schema([
                                Toggle::make('single_use')
                                    ->disabledOn('create')
                                    ->columns(1),
                                Toggle::make('forward_query_params')
                                    ->disabledOn('create')
                                    ->columns(1),
                                Toggle::make('track_visits')
                                    ->label('Track Visits')
                                    ->helperText('Record link accesses'),

                                Toggle::make('track_ip_address')
                                    ->label('Track IP Address'),

                                Toggle::make('track_browser')
                                    ->label('Track Browser'),
                            ]),
                    ]),

                Section::make('Activation Period')
                    ->schema([
                        DateTimePicker::make('activated_at')
                            ->label('Activation Date')
                            ->helperText('When the link becomes active'),

                        DateTimePicker::make('deactivated_at')
                            ->label('Expiration Date')
                            ->helperText('When the link will deactivate'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination_url')
                    ->copyable()
                    ->limit(50)
                    ->color('primary')
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    })
                    ->searchable()
                    ->sortable(),

                // Description column
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (! $state || strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }

                        return $state;
                    }),

                // Price column
                Tables\Columns\TextColumn::make('price')
                    ->label('Cost')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return '-';
                        }

                        $symbols = [
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            'BGN' => 'лв',
                            'JPY' => '¥',
                            'CAD' => 'C$',
                            'AUD' => 'A$',
                        ];

                        $currency = $record->currency ?? 'USD';
                        $symbol = $symbols[$currency] ?? $currency;

                        return $symbol.number_format($state, 2);
                    })
                    ->sortable()
                    ->toggleable(),

                // UTM Parameters columns
                Tables\Columns\TextColumn::make('utm_source')
                    ->label('Source')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('utm_medium')
                    ->label('Medium')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('utm_campaign')
                    ->label('Campaign')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('default_short_url')
                    ->copyable()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('visits_count')
                    ->label('Visits')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total Visits'))
                    ->counts('visits'),
                Tables\Columns\TextColumn::make('activated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deactivated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('published_at')
                    ->form([
                        DatePicker::make('activated_at'),
                        DatePicker::make('deactivated_at'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['activated_at'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('activated_at', '>=', $date),
                            )
                            ->when(
                                $data['deactivated_at'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('deactivated_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['deactivated_at'] ?? null) {
                            $indicators['deactivated_at'] = 'Activated from '.Carbon::parse($data['published_from'])->toFormattedDateString();
                        }
                        if ($data['deactivated_at'] ?? null) {
                            $indicators['deactivated_at'] = 'Deactivated until '.Carbon::parse($data['deactivated_at'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('copy')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->color('gray')
                    ->action(function ($record) {
                        // The URL will be copied client-side via JavaScript
                    })
                    ->extraAttributes(function ($record) {
                        return [
                            'x-on:click' => 'window.navigator.clipboard.writeText($el.dataset.url)',
                            'data-url' => $record->default_short_url,
                        ];
                    })
                    ->successNotificationTitle('URL copied to clipboard'),
                Action::make('toggleActivation')
                    ->label(function ($record) {
                        return $record->deactivated_at ? 'Activate' : 'Deactivate';
                    })
                    ->icon(function ($record) {
                        return $record->deactivated_at ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                    })
                    ->color(function ($record) {
                        return $record->deactivated_at ? 'success' : 'danger';
                    })
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $wasDeactivated = $record->deactivated_at !== null;

                        $record->update([
                            'deactivated_at' => $wasDeactivated ? null : now(),
                        ]);

                        Notification::make()
                            ->title($wasDeactivated ? 'URL activated' : 'URL deactivated')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('toggleTracking')
                        ->label('Toggle Tracking')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'track_visits' => ! $record->track_visits,
                                ]);
                            }

                            Notification::make()
                                ->title('Tracking status updated')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('deactivateUrls')
                        ->label('Deactivate URLs')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'deactivated_at' => now(),
                                ]);
                            }

                            Notification::make()
                                ->title('URLs deactivated')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('activateUrls')
                        ->label('Activate URLs')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'deactivated_at' => null,
                                ]);
                            }

                            Notification::make()
                                ->title('URLs activated')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(10)
                            ->schema([
                                Group::make([
                                    Group::make([
                                        TextEntry::make('destination_url')
                                            ->limit(50)
                                            ->copyable()
                                            ->color('primary'),
                                        TextEntry::make('description')
                                            ->markdown(),
                                        TextEntry::make('price')
                                            ->label('Campaign Cost')
                                            ->formatStateUsing(function ($state, $record) {
                                                if (! $state) {
                                                    return 'No cost set';
                                                }

                                                $symbols = [
                                                    'USD' => '$',
                                                    'EUR' => '€',
                                                    'GBP' => '£',
                                                    'BGN' => 'лв',
                                                    'JPY' => '¥',
                                                    'CAD' => 'C$',
                                                    'AUD' => 'A$',
                                                ];

                                                $currency = $record->currency ?? 'USD';
                                                $symbol = $symbols[$currency] ?? $currency;

                                                return $symbol.number_format($state, 2).' '.$currency;
                                            }),
                                        TextEntry::make('default_short_url')
                                            ->copyable()
                                            ->color('primary'),
                                    ]),
                                ])->columnSpan(4),
                                Group::make([
                                    Group::make([
                                        ImageEntry::make('destination_url')
                                            ->label('QR Code')
                                            ->state(fn () => self::getQrCode($schema->getRecord()->default_short_url)),
                                    ]),
                                ])->columnSpan(2),
                                Group::make([
                                    Group::make([
                                        TextEntry::make('activated_at'),
                                        TextEntry::make('deactivated_at'),
                                    ]),
                                ])->columnSpan(2),
                                Group::make([
                                    Group::make([
                                        TextEntry::make('created_at'),
                                        TextEntry::make('updated_at'),
                                    ]),
                                ])->columnSpan(2),
                            ]),
                    ]),
                // UTM Parameters Section
                Section::make('UTM Parameters')
                    ->schema([
                        Group::make([
                            TextEntry::make('utm_source')
                                ->label('Source')
                                ->badge()
                                ->color('info'),
                            TextEntry::make('utm_medium')
                                ->label('Medium')
                                ->badge()
                                ->color('warning'),
                            TextEntry::make('utm_campaign')
                                ->label('Campaign')
                                ->badge()
                                ->color('success'),
                            TextEntry::make('utm_term')
                                ->label('Term')
                                ->badge()
                                ->color('gray'),
                            TextEntry::make('utm_content')
                                ->label('Content')
                                ->badge()
                                ->color('gray'),
                        ])->columns(5),
                    ])
                    ->collapsible(),
                Section::make('Tracking Settings')
                    ->schema([
                        Group::make([
                            IconEntry::make('single_use'),
                            IconEntry::make('forward_query_params'),
                            IconEntry::make('track_ip_address'),
                            IconEntry::make('track_operating_system'),
                            IconEntry::make('track_operating_system_version'),
                            IconEntry::make('track_browser'),
                            IconEntry::make('track_browser_version'),
                            IconEntry::make('track_referer_url'),
                            IconEntry::make('track_device_type'),
                        ])->columns(5),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getQrCode(string $url)
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(150, 1, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($url);

        $trimmed = trim(substr($svg, strpos($svg, "\n") + 1));

        $url = 'data:image/svg+xml;base64,'.base64_encode($trimmed);

        return $url;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            ShortUrlStats::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewShortUrl::class,
            Pages\EditShortUrl::class,
            Pages\ListShortUrlVisits::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShortUrls::route('/'),
            'create' => Pages\CreateShortUrl::route('/create'),
            'view' => Pages\ViewShortUrl::route('/{record}'),
            'edit' => Pages\EditShortUrl::route('/{record}/edit'),
            'visits' => Pages\ListShortUrlVisits::route('/{record}/visits'),
        ];
    }
}
