<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ProductQuotation extends ViewRecord
{
    protected static string $resource = ProductResource::class;
    protected static string $view = 'filament.resources.product-resource.pages.product-quotation';
    protected static ?string $breadcrumb = 'Product Quotation';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public Model|int|string|null $record;

    public function getHeading(): string
    {
        return $this->record->name ?? '';
    }
}
