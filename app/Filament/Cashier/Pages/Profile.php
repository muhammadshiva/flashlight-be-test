<?php

namespace App\Filament\Cashier\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class Profile extends BaseEditProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament-panels::pages.auth.edit-profile';

    public function getTitle(): string
    {
        return __('filament-panels::pages/auth/edit-profile.title');
    }
}
