<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\EmployeeResource\Pages;
use App\Filament\App\Resources\EmployeeResource\RelationManagers;
use App\Models\City;
use App\Models\Employee;
use App\Models\State;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Forms\Components\Section::make('Relationships')
                ->schema([
                    Forms\Components\Select::make('country_id')//create state relationship for state modal
                        ->relationship(name:'country', titleAttribute:'name') //create country relationship for state modal
                        ->searchable()
                        ->preload()
                        ->live() //country field changes
                        ->afterStateUpdated(function(Set $set){
                            $set('city_id', null);
                            $set('state_id', null);
                        })
                        ->required(),
                    Forms\Components\Select::make('state_id')
                        ->options(fn(Get $get): Collection => State::query()
                            ->where('country_id', $get('country_id'))
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('city_id', null))
                        ->required(),
                    Forms\Components\Select::make('city_id')
                        ->options(fn(Get $get): Collection => City::query()
                            ->where('state_id', $get('state_id'))
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('department_id')
                        ->relationship(
                            name:'department',
                            titleAttribute:'name',
                            modifyQueryUsing: fn (Builder $query) => $query->whereBelongsTo(Filament::getTenant())
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),
                ])->columns(2),
            Forms\Components\Section::make('User Name')
                ->description('Put the user name details in.')
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('middle_name')
                        ->required()
                        ->maxLength(255),
                ])->columns(3),
            Forms\Components\Section::make('User Address')
                ->schema([
                    Forms\Components\TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip_code')
                    ->required()
                    ->maxLength(255),
                ])->columns(2),
            Forms\Components\Section::make('Dates')
                ->schema([
                Forms\Components\DatePicker::make('date_of_birth')
                    ->native(false)
                    ->displayFormat('d/m/y')
                    ->required(),
                Forms\Components\DatePicker::make('date_hired')
                    ->native(false)
                    ->displayFormat('d/m/y')
                    ->required()
                    // ->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('zip_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('date_hired')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true) ,
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Employee deleted')
                            ->body('The Employee updated successfully.')
                )
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Employee Info')
                ->schema([
                    Section::make('Relationships')
                        ->schema([
                            TextEntry::make('Country.name'),
                            TextEntry::make('State.name'),
                            TextEntry::make('City.name'),
                            TextEntry::make('Department.name'),
                        ])->columns(2),
                    Section::make('Name')
                        ->schema([
                            TextEntry::make('first_name'),
                            TextEntry::make('middle_name'),
                            TextEntry::make('last_name'),
                        ])->columns(2),
                    Section::make('Address')
                        ->schema([
                            TextEntry::make('address'),
                            TextEntry::make('zip_code'),
                        ])->columns(2),
                    Section::make('Dates')
                        ->schema([
                            TextEntry::make('date_of_birth'),
                            TextEntry::make('date_hired'),
                        ])->columns(2),
                ])
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            //'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
