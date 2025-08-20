<?php

namespace A21ns1g4ts\FilamentShortUrl\Filament\Resources;

use A21ns1g4ts\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;
use A21ns1g4ts\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStats;
use AshAllenDesign\ShortURL\Models\ShortURL;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ShortUrlResource extends Resource
{
    protected static ?string $modelLabel = 'Short URL';

    protected static ?string $pluralModelLabel = 'Short URLs';

    protected static ?string $navigationBadgeColor = 'primary';

    protected static ?string $model = ShortURL::class;

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
                        Forms\Components\TextInput::make('destination_url')
                            ->label('Destination URL')
                            ->placeholder('https://example.com/long-url')
                            ->helperText('The original URL to be shortened'),

                        Forms\Components\TextInput::make('default_short_url')
                            ->label('Short URL')
                            ->helperText('Click to copy'),

                        Forms\Components\TextInput::make('url_key')
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
                        Forms\Components\DateTimePicker::make('activated_at')
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
                        Forms\Components\DatePicker::make('activated_at'),
                        Forms\Components\DatePicker::make('deactivated_at'),
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
                            $indicators['deactivated_at'] = 'Activated from ' . Carbon::parse($data['published_from'])->toFormattedDateString();
                        }
                        if ($data['deactivated_at'] ?? null) {
                            $indicators['deactivated_at'] = 'Deactivated until ' . Carbon::parse($data['deactivated_at'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()
                    ->action(function () {
                        Notification::make()
                            ->title('You can\'t bulk delete for now! :). This will be implemented in the future.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('destination_url')
                                    ->limit(50)
                                    ->copyable()
                                    ->color('primary'),
                         TextEntry::make('default_short_url')
                                    ->copyable()
                                    ->color('primary'),
                           ImageEntry::make('destination_url')
                                    ->label('QR Code')
                                    ->state(fn () => self::getQrCode($schema->getRecord()->default_short_url)),
                    ]),
                    Section::make()
                    ->schema([
                            TextEntry::make('activated_at'),
                            TextEntry::make('deactivated_at'),
                            TextEntry::make('created_at'),
                            TextEntry::make('updated_at'),
                    ]),
                ]),
                Section::make()
                    ->schema([
                            IconEntry::make('single_use'),
                            IconEntry::make('forward_query_params'),
                            IconEntry::make('track_ip_address'),
                            IconEntry::make('track_operating_system'),
                            IconEntry::make('track_operating_system_version'),
                            IconEntry::make('track_browser'),
                            IconEntry::make('track_browser_version'),
                            IconEntry::make('track_referer_url'),
                            IconEntry::make('track_device_type'),
                    ]),
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

        $url = 'data:image/svg+xml;base64,' . base64_encode($trimmed);

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
