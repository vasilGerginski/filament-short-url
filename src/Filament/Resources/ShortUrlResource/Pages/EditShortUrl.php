<?php

namespace VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use VasilGerginski\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditShortUrl extends EditRecord
{
    protected static string $resource = ShortUrlResource::class;

    public function getTitle(): string|Htmlable
    {
        /* @var \AshAllenDesign\ShortURL\Models\ShortURL $record */
        $record = $this->getRecord();

        return $record->default_short_url ?? 'Unknown';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('create')
                ->label('New')
                ->url(CreateShortUrl::getUrl()),
        ];
    }
}
