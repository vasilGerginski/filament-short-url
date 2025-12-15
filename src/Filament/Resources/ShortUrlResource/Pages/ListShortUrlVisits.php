<?php

namespace VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListShortUrlVisits extends ManageRelatedRecords
{
    protected static string $resource = ShortUrlResource::class;

    protected static string $relationship = 'visits';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    public function getTitle(): string|Htmlable
    {
        /* @var \AshAllenDesign\ShortURL\Models\ShortURL $record */
        $record = $this->getRecord();

        return isset($record->default_short_url) ? $record->default_short_url : 'Visits';
    }

    public function getBreadcrumb(): string
    {
        return 'Visits';
    }

    public static function getNavigationLabel(): string
    {
        return 'Visits';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([])
            ->columns(1);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                TextEntry::make('short_url_id'),
                TextEntry::make('ip_address'),
                TextEntry::make('operating_system'),
                TextEntry::make('operating_system_version'),
                TextEntry::make('browser'),
                TextEntry::make('browser_version'),
                TextEntry::make('visited_at'),
                TextEntry::make('referer_url'),
                TextEntry::make('device_type'),
            ])->columns(5);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('visited_at')
                    ->label('Visited At')
                    ->dateTime('M j, Y H:i')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('operating_system')
                    ->label('OS')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('operating_system_version')
                    ->label('OS Version')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('browser')
                    ->label('Browser')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('browser_version')
                    ->label('Browser Version')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('referer_url')
                    ->label('Referer URL')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'mobile' => 'warning',
                        'tablet' => 'info',
                        'desktop' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match (strtolower($state)) {
                        'mobile' => 'heroicon-o-device-phone-mobile',
                        'tablet' => 'heroicon-o-device-tablet',
                        'desktop' => 'heroicon-o-computer-desktop',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');

    }
}
