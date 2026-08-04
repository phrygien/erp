<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->rule('min:8')
                    ->same('passwordConfirmation')
                    ->autocomplete('new-password')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->live(debounce: 500)
                    ->label(__('Mot de passe')),
                TextInput::make('passwordConfirmation')
                    ->password()
                    ->revealable()
                    ->required()
                    ->visible(fn (callable $get) => filled($get('password')))
                    ->dehydrated(false)
                    ->label(__('Confirmer le mot de passe')),
            ]);
    }
}
