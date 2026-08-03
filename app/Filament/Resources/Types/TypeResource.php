<?php

namespace App\Filament\Resources\Types;

use App\Filament\Resources\Types\Pages\ManageTypes;
use App\Models\Type;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TypeResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'Catalogues';
    protected static ?string $model = Type::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('state')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                    ])
                    ->required()
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                ToggleColumn::make('state')
                    ->label('Actif')
                    ->getStateUsing(fn ($record) => $record->state === 'active')
                    ->updateStateUsing(function ($record, $state) {
                        $record->state = $state ? 'active' : 'inactive';
                        $record->save();
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body($record->name . ' est maintenant ' . ($state ? 'actif' : 'inactif'))
                            ->success()
                            ->send();
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTypes::route('/'),
        ];
    }
}
