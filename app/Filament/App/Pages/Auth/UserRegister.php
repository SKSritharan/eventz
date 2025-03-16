<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register;
use Illuminate\Database\Eloquent\Model;

class UserRegister extends Register
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(255)
                    ]),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = $this->getUserModel()::create($data);
        $user->assignRole('User');

        return $user;
    }
}
