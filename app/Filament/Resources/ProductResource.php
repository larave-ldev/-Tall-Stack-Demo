<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Infolists\Components\ImageSlider;
use App\Jobs\BulkUpdateCustomOptionsPrice;
use App\Jobs\DeleteBulkProductFromMagentoJob;
use App\Jobs\MagentoProductBasedProductUpdateJob;
use App\Jobs\MagentoProductBasedSetupFeeUpdateJob;
use App\Models\Product;
use App\Models\ProductMedia;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Mavinoo\Batch\Batch;

class ProductResource extends Resource
{
    protected static ?int $navigationSort = 4;
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Enabled Products';

    protected static ?string $breadcrumb = 'Enabled Products';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Overview')->schema([
                    Grid::make(3)->schema([
                        Group::make([
                            ImageSlider::make('id')->view('filament.resources.product-resource.pages.image-slick-carousel-page'),
                        ]),
                        Group::make([
                            ImageEntry::make('downloaded_hero_image')
                                ->label('')
                                ->extraImgAttributes(['loading' => 'lazy'])
                                ->state(function (Model $record) {
                                    return $record->downloaded_hero_image ?: null;
                                })->hiddenLabel()->grow(false)->circular(),
                            TextEntry::make('name'),
                            TextEntry::make('code'),
                            TextEntry::make('custom_option_price')->label('Current Applied Price (In %)'),
                            ViewEntry::make('custom_option_price_update')
                                ->label('Custom Option Price (In %)')
                                ->view('filament.infolists.update.custom-option-price')
                                ->registerActions([
                                    Action::make('updateCustomOptionPrice')
                                        ->label('Update Price')
                                        ->form([
                                            TextInput::make('price_display')
                                                ->label('Current price (In %)')
                                                ->default(fn (Model $record) => $record->custom_option_price)
                                                ->readOnly(),
                                            TextInput::make('custom_option_price_update')
                                                ->label('New Price (In %)')
                                                ->required()
                                                ->placeholder('Enter new price')
                                                ->default(fn (Model $record) => $record->custom_option_price ?? '')
                                                ->numeric(true)
                                                ->maxValue(100)
                                                ->minValue(0.01),
                                        ])->size('xs')
                                        ->action(function (array $data, Product $record) {
                                            // Update on Magento
                                            if ($data['custom_option_price_update'] !== $data['price_display']) {
                                                Product::where(['id' => $record->id])
                                                    ->update(['custom_option_price' => $data['custom_option_price_update']]);
                                                sleep(3);
                                                Artisan::call('magento-single-product-update ' . $record->id);
                                            } else {
                                                Notification::make('update_price')
                                                    ->title('Price ' . $data['price_display'] . ' already applied.')
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                        ->modalWidth('sm'),
                                ]),
                        ]),
                        Group::make([
                            TextEntry::make('country'),
                            TextEntry::make('price_currencies')->label('Currencies')
                                ->badge()
                                ->getStateUsing(fn (Model $record
                                ): ?array => $record->price_currencies ? json_decode($record->price_currencies,
                                    true) : []),
                            TextEntry::make('last_changed_at')->label('Last Changed At')
                                ->badge()
                                ->dateTime('d/m/Y h:i a')
                                ->color('success'),
                            TextEntry::make('productType.name')->label('Product Type'),
                            TextEntry::make('productTypeSub.name')->label('Product Sub Type'),
                            TextEntry::make('discontinued')->label('APPA Status')
                                ->state(fn (Model $record): string => $record->discontinued === 0 ? 'Enabled' : 'Disabled')->badge()
                                ->color(fn (Model $record): string => $record->discontinued === 0 ? 'success' : 'danger'),

                        ]),
                        TextEntry::make('magento_price')->label('Magento Price')
                            ->state(function (Model $record): string {
                                $increasePrice = $record->custom_option_price ?: 0;
                                $magentoPrice = $record->magento_price ?: 0;
                                $calculatedPrice = ($magentoPrice * $increasePrice) / 100;
                                $result = round(($magentoPrice + $calculatedPrice), 2);

                                return $result ? '$' . $result : '';
                            }),
                        TextEntry::make('magento_status')->label('Magento Status')
                            ->state(fn (Model $record): string => $record->magento_status == 1 ? 'Enabled' : 'Disabled')->badge()
                            ->color(fn (Model $record): string => $record->magento_status == 1 ? 'success' : 'danger'),

                        TextEntry::make('magento_setup_cost')->label('Magento Setup Cost')->state(fn (Model $record) => $record->magento_setup_cost ? '$' . $record->magento_setup_cost : ''),
                        TextEntry::make('magento_delivery_price')->label('Magento Delivery Price')->state(fn (Model $record) => $record->magento_delivery_price ? '$' . $record->magento_delivery_price : ''),
                        IconEntry::make('magento_id')
                            ->state(fn (Model $record): bool => $record->magento_id ? true : false)
                            ->icon(fn (Model $record): string => $record->magento_id ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                            ->color(fn (Model $record): string => $record->magento_id ? 'success' : 'danger')
                            ->label('Is Available'),
                        TextEntry::make('description')
                            ->prose()
                            ->markdown()->html()->columnSpanFull(),
                    ]),
                ])->collapsible(),
                Section::make('Supplier Details')->schema([
                    Grid::make(3)
                        ->schema([
                            TextEntry::make('supplier.name')->label('Name'),
                            TextEntry::make('supplier_country')->label('Country'),
                            TextEntry::make('supplier_appa_member_number')->label('APPA Member No.'),
                            TextEntry::make('supplier_brand')->label('Brand'),
                            TextEntry::make('supplier_label')->label('Label'),
                            TextEntry::make('supplier_catalogue')->label('Catalogue'),
                            TextEntry::make('supplier_category')->label('Category'),
                            TextEntry::make('supplier_subcategory')->label('Sub category'),
                            TextEntry::make('supplier_website_page')->label('Website')
                                ->url(fn (Model $record): ?string => $record->supplier_website_page)
                                ->openUrlInNewTab()
                                ->columnSpanFull()
                                ->icon('heroicon-m-globe-asia-australia')
                                ->color('primary'),
                        ]),
                ])->collapsible(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            ]);
    }

    public static function getMedias(int $productId)
    {
        return ProductMedia::select('downloaded_url')
            ->whereType('image')
            ->whereNotNull('downloaded_url')
            ->whereProductId($productId)
            ->get();
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\VariantsRelationManager::class,
            ProductResource\RelationManagers\ExtraInfosRelationManager::class,
            ProductResource\RelationManagers\ColourListsRelationManager::class,
            ProductResource\RelationManagers\ColourSupplierTextsRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('downloaded_hero_image')->label('Image')
                    ->state(function (Model $record) {
                        return $record->downloaded_hero_image ?? null;
                    })->width('100px')->height('100px'),
                Tables\Columns\TextColumn::make('name')
                    ->wrap()
                    ->copyable()
//                    ->words(3)
//                    ->tooltip(fn (Model $record): string => $record->name ?: '')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('magento_price')
                    ->label('Price')
                    ->sortable()
                    ->searchable()
                    ->state(function (Model $record): string {
                        $increasePrice = $record->custom_option_price ?: 0;
                        $magentoPrice = $record->magento_price ?: 0;
                        $calculatedPrice = ($magentoPrice * $increasePrice) / 100;

                        $result = round(($magentoPrice + $calculatedPrice), 2);

                        return $result ? '$' . $result : '';
                    }),
                Tables\Columns\TextColumn::make('productType.name')->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Supplier')->sortable()->searchable()->copyable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('discontinued')
                    ->label('APPA Status')
                    ->state(fn (Model $record): string => $record->discontinued === 0 ? 'Enabled' : 'Disabled')->badge()
                    ->color(fn (Model $record): string => $record->discontinued === 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Is Enabled')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_option_price')
                    ->label('Current Applied Price')
                    ->state(fn (Model $record): string => $record->custom_option_price ? $record->custom_option_price . '%' : '')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('magento_id')
                    ->state(fn (Model $record): bool => $record->magento_id ? true : false)
                    ->icon(fn (Model $record): string => $record->magento_id ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (Model $record): string => $record->magento_id ? 'success' : 'danger')
                    ->label('Is Available')
                    ->sortable(),
                Tables\Columns\TextColumn::make('magento_status')
                    ->label('Status')
                    ->state(fn (Model $record): string => $record->magento_status == 1 ? 'Enabled' : 'Disabled')->badge()
                    ->color(fn (Model $record): string => $record->magento_status == 1 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('magento_setup_cost')
                    ->state(fn (Model $record) => $record->magento_setup_cost ? '$' . $record->magento_setup_cost : '')
                    ->label('Setup Cost')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('magento_delivery_price')
                    ->state(fn (Model $record) => $record->magento_delivery_price ? '$' . $record->magento_delivery_price : '')
                    ->label('Delivery Price')
                    ->sortable()
                    ->searchable(),
            ])->defaultSort('id', 'asc')
            ->filters([
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name', fn (Builder $query) => $query->where('is_sync_on_magento', true))
                    ->preload()
                    ->multiple()
                    ->searchable(),

                SelectFilter::make('productType')
                    ->relationship('productType', 'name')
                    ->preload()
                    ->multiple()
                    ->searchable(),

                Filter::make('discontinued')->label('Discontinued From Supplier')
                    ->query(fn (Builder $query): Builder => $query->where('discontinued', true)),

                Filter::make('name')->label('Empty Name')
                    ->query(fn (Builder $query): Builder => $query->where('name', '')),

                SelectFilter::make('is_enabled')->label('Is Enabled')
                    ->options([
                        1 => 'True',
                        0 => 'False',
                    ]),

                Filter::make('magento_id')->label('Available In Magento')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('magento_id')),
            ])
            ->actions([
                Tables\Actions\Action::make('product_quotation')
                    ->label('')
                    ->color('info')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn (Model $record) => ProductResource::getUrl('product-quotation', [$record->id]))
                    ->tooltip('Product Quotation'),
                Tables\Actions\Action::make('id')->label('Update on Magento')
                    ->visible(fn (Model $record, $livewire): bool => (($record->magento_id && $record->supplier->is_sync_on_magento) ? true : false) && $livewire->activeTab !== 'archived' && $livewire->activeTab === 'on_site')
                    ->tooltip('Update on Magento')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('gray')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->action(fn (Model $record) => Artisan::call('magento-single-product-update ' . $record->id)),
                Tables\Actions\ViewAction::make()->label('')->tooltip('View Product'),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->paginated([5, 10, 25, 50, 100, 200])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_revert_archive')
                        ->label('Undo Archived')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'archived')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->chunk(100)->each(function ($chunks) {
                                $ids = [];
                                foreach ($chunks as $chunk) {
                                    $ids[] = ['id' => $chunk->id];

                                }
                                Product::whereIn('id', $ids)->update(['is_archived' => false]);
                            }
                            );
                            Notification::make('revert_archived_products')
                                ->title('Selected products has been undo successfully.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()->dispatch('reload'),
                    Tables\Actions\BulkAction::make('bulk_archive')
                        ->label('Archive')
                        ->visible(fn ($livewire): bool => $livewire->activeTab !== 'archived' && $livewire->activeTab === 'new')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->chunk(100)->each(function ($chunks) {
                                $ids = [];
                                foreach ($chunks as $chunk) {
                                    $ids[] = ['id' => $chunk->id];

                                }
                                Product::whereIn('id', $ids)->update(['is_archived' => true]);
                            }
                            );
                            Notification::make('archived_products')
                                ->title('Selected products has been archived successfully.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()->dispatch('reload'),
                    BulkAction::make('update_custom_options_price')
                        ->label('Update Custom Options Price')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'on_site')
                        ->form([
                            TextInput::make('custom_option_price_update')
                                ->label('New Price (In %)')
                                ->required()
                                ->placeholder('Enter new price')
                                ->numeric()
                                ->maxValue(100)
                                ->minValue(0.01),
                        ])->modalWidth('sm')
                        ->action(function (array $data, Collection $records): void {
                            $data['user_id'] = auth()->user()->id;
                            foreach ($records as $record) {
                                /*
                                 * It will check name is not blank and product must be existed on Magento,
                                 * then only custom option will be updated and created
                                 */
                                if ($record->name != '' && $record->magento_id) {
                                    BulkUpdateCustomOptionsPrice::dispatch($record, $data);
                                }
                            }
                            sleep(5);
                            Notification::make('bulk_update_price')
                                ->title('Bulk process running in background.')
                                ->body('You will get notification after done process.')
                                ->info()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->dispatch('reload'),

                    BulkAction::make('enable_on_magento')
                        ->label('Enable On Magento')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'on_site')
                        ->tooltip("Effects on Magento's existing products only")
                        ->requiresConfirmation()
                        ->action(function (Collection $records, BulkAction $action): void {
                            foreach ($records as $record) {
                                /*
                                 * It will enable the products on magento if exist
                                 *
                                 * */
                                MagentoProductBasedProductUpdateJob::dispatch($record->id, 1);
                                sleep(3);
                                // Send a notification
                                Notification::make()
                                    ->title('Enabled on magento successfully!')
                                    ->body('Update on magento process has been started.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            }
                        })->deselectRecordsAfterCompletion()->dispatch('reload'),

                    BulkAction::make('disable_on_magento')
                        ->label('Disable On Magento')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'on_site')
                        ->tooltip("Effects on Magento's existing products only")
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                /*
                                 * It will disable the products on magento if exist
                                 * */
                                MagentoProductBasedProductUpdateJob::dispatch($record->id, 2);
                                sleep(3);
                                // Send a notification
                                Notification::make()
                                    ->title('Disabled from magento successfully!')
                                    ->body('Update on magento process has been started.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            }
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('send_to_magento')
                        ->label('Send To Magento')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'new')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, BulkAction $action, Batch $batch): void {
                            self::sendToMagento($records, $action, $batch);
                        })->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_setup_fee')
                        ->label('Setup Cost & Delivery Price')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'on_site')
                        ->modalDescription('Note: Blank values will be skipped during the update on Magento.')
                        ->form([
                            TextInput::make('magento_setup_cost')
                                ->label('Magento Setup Cost (In $)')
                                ->placeholder('Enter magento setup cost')
                                ->minValue('0')
                                ->numeric(),
                            TextInput::make('magento_delivery_price')
                                ->label('Magento Delivery Price (In $)')
                                ->placeholder('Enter magento delivery price')
                                ->minValue('0')
                                ->numeric(),
                        ])->modalWidth('sm')
                        ->action(function (array $data, Collection $records): void {
                            if ($data['magento_setup_cost'] != '' || $data['magento_delivery_price'] != '') {
                                foreach ($records as $record) {
                                    // It will update setup fees of products on magento if exist
                                    MagentoProductBasedSetupFeeUpdateJob::dispatch($data, $record);
                                }
                                sleep(3);
                                // Send a notification
                                Notification::make('bulk_setup_fee_notification')
                                    ->title('Setup fees update process has been started.')
                                    ->body('It will take few minutes.')
                                    ->success()
                                    ->duration(5000)
                                    ->send();
                            } else {
                                // Send a notification
                                Notification::make('bulk_setup_fee_notification')
                                    ->title('Oops! both values are blank, so nothing to happens.')
                                    ->info()
                                    ->send();
                            }

                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulk_delete')
                        ->label('Delete From Magento')
                        ->visible(fn ($livewire): bool => $livewire->activeTab === 'on_site')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->chunk(100)->each(function ($records) {
                                DeleteBulkProductFromMagentoJob::dispatch($records);
                            });

                            Notification::make('bulk_delete_notification')
                                ->title('Delete from Magento process has been started.')
                                ->body('It will take few minutes. Please reload the page frequently for update.')
                                ->persistent()
                                ->success()
                                ->send();

                            sleep(15);

                            // Reload page
                            redirect(request()->header('Referer'));
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_update_from_promodata')
                        ->label('Update From Promodata')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): ?bool {
                            if (count($records) > 10) {
                                Notification::make('crossed_the_limit')
                                    ->title('Sorry! You cannot update more than 10 products at a time.')
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                return false;
                            }

                            $records->chunk(10)->each(function ($chunks) {
                                foreach ($chunks as $chunk) {
                                    Artisan::call('promodata-single-latest-product-update ' . $chunk->promodata_id);
                                }
                            }
                            );
                            Notification::make('update_product')
                                ->title('Selected product has been updated successfully.')
                                ->success()
                                ->send();

                            return true;
                        })
                        ->deselectRecordsAfterCompletion()->dispatch('reload'),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereHas('supplier',
                    function ($query) {
                        $query->where('is_sync_on_magento', true);
                    })
            )
            ->selectCurrentPageOnly();
    }

    /**
     * @throws Halt
     */
    public static function sendToMagento(Collection $records, BulkAction $action, $batch): void
    {
        $productData = [];
        foreach ($records as $record) {
            if (! $record->magento_id) {
                $productData[] = [
                    'id' => $record->id,
                    'is_enabled' => true,
                    'updated_at' => now(),
                ];
            }

        }

        $productInstance = new Product;
        $index = 'id';
        $batch->update($productInstance, $productData, $index);

        sleep(3);
        // Send a notification
        Notification::make()
            ->title('Request Sent Successfully')
            ->body('The process of sending products to Magento will start at 11:00 PM as per AU time')
            ->success()
            ->duration(10000)
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}/view/'),
            'product-quotation' => Pages\ProductQuotation::route('/{record}/product-quotation'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewProduct::class,
            Pages\ProductQuotation::class,
        ]);
    }
}
