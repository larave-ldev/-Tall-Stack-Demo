<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected ?string $heading = 'Enabled Products';

    public function getTabs(): array
    {
        return [
            'new' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNull('magento_id')
                    ->where('is_archived', false)),
            'on_site' => Tab::make()
                ->label('On Site')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('magento_id')
                    ->where('is_archived', false)),
            'archived' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('is_archived', true)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
