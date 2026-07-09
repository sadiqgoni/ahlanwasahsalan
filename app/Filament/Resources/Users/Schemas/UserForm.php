<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->description('Login details for this staff member.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Full name')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required(),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Leave empty to keep the current password.')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ])
                    ->columns(2),

                Section::make('Role & Security')
                    ->description('The role decides what this person can see and do. Only the owner can void receipts, manage the menu, and manage staff.')
                    ->schema([
                        Select::make('role')
                            ->options([
                                'owner' => 'Owner — full control',
                                'cashier' => 'Cashier — POS sales only',
                                'accountant' => 'Accountant — read-only reports',
                            ])
                            ->default('cashier')
                            ->required(),
                        TextInput::make('pin')
                            ->label('Void PIN')
                            ->password()
                            ->revealable()
                            ->numeric()
                            ->minLength(4)
                            ->maxLength(6)
                            ->helperText('Owner only — approves voided receipts. Leave empty to keep current PIN.')
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        Toggle::make('is_active')
                            ->label('Account active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
