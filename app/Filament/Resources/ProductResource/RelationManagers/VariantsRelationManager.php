<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Resources\ProductResource\Pages\ViewVariantPrices;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public string $status;

    public static function getPages(): array
    {
        return [
            'view' => ViewVariantPrices::route('/{record}/view'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Variant Base Price')->schema([
                    RepeatableEntry::make('productVariantPrice')->label('')
                        ->schema([
                            TextEntry::make('qty')->color('success'),
                            TextEntry::make('price')->color('warning'),
                        ])->columns(2)->grid(6),
                ]),
                Section::make('Additions')->schema([
                    RepeatableEntry::make('productAddition')->label('')
                        ->schema([
                            TextEntry::make('type')->state(function (Model $record) {
                                return $record->type ? $record->type : $record->description;
                            }),
                            TextEntry::make('setup'),
                            TextEntry::make('currency'),
                            TextEntry::make('lead_time'),
                            TextEntry::make('description')->columnSpanFull(),
                            Section::make('Prices')->schema([
                                RepeatableEntry::make('productAdditionPrice')->label('')
                                    ->schema([
                                        TextEntry::make('qty')->color('success'),
                                        TextEntry::make('price')->color('warning'),
                                    ])->columns(2)->grid(6),
                            ]),
                        ])->columns(5),

                ]),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {

        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('setup'),
                Tables\Columns\TextColumn::make('indent'),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('lead_time'),
                Tables\Columns\TextColumn::make('description')->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->tooltip('View Prices & Additions')
                    ->url(fn (Model $record) => $record->id ? Filament::getHomeUrl() . '/product-variants/' . $record->id . '/view' : '#')->openUrlInNewTab(true),
            ])
            ->bulkActions([
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
