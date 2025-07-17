<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HouseResource\Pages;
use App\Filament\Resources\HouseResource\RelationManagers;
use App\Models\Facilities;
use App\Models\House;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HouseResource extends Resource
{
    protected static ?string $model = House::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('IDR'),
                        Forms\Components\Select::make('certificate')
                            ->options([
                                'SHM' => 'SHM',
                                'HGB' => 'HGB',
                                'Patches' => 'Patches',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('thumbnail')
                            ->image()
                            ->required(),
                        Forms\Components\Repeater::make('photos')
                            ->relationship('photos')
                            ->schema([
                                Forms\Components\FileUpload::make('photo')
                                    ->required(),
                            ]),
                        Forms\Components\Repeater::make('facilities')
                            ->relationship('facilities')
                            ->schema([
                                Forms\Components\Select::make('facility_id')
                                    ->label('Facility')
                                    ->options(Facilities::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                            ]),
                    ]),
                Fieldset::make('Additional')
                    ->schema([
                        Forms\Components\Textarea::make('about')
                            ->required(),
                        Forms\Components\Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('electric')
                            ->required()
                            ->numeric()
                            ->prefix('Watts'),
                        Forms\Components\TextInput::make('land_area')
                            ->required()
                            ->numeric()
                            ->prefix('m²'),
                        Forms\Components\TextInput::make('building_area')
                            ->required()
                            ->numeric()
                            ->prefix('m²'),
                        Forms\Components\TextInput::make('bedroom')
                            ->required()
                            ->numeric()
                            ->prefix('Unit'),
                        Forms\Components\TextInput::make('bathroom')
                            ->required()
                            ->numeric()
                            ->prefix('Unit'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category.name'),
                Tables\Columns\TextColumn::make('city.name'),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHouses::route('/'),
            'create' => Pages\CreateHouse::route('/create'),
            'edit' => Pages\EditHouse::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
