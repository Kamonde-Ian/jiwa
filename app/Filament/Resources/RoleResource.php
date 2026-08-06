<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage roles') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true)->maxLength(255),
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->relationship('permissions', 'name')
                            ->columns(2)
                            ->descriptions(
                                collect(\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS)
                                    ->mapWithKeys(fn ($p) => [$p => ucfirst($p)])
                                    ->all(),
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions'),
                Tables\Columns\TextColumn::make('users_count')->label('Users')->counts('users'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record) => ! in_array($record->name, ['admin', 'user'], true)),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
