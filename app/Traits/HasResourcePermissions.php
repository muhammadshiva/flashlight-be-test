<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasResourcePermissions
{
    public static function canAccess(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $resourceName = static::getModelLabel();
        return Auth::user()->can("view {$resourceName}");
    }

    public static function canCreate(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $resourceName = static::getModelLabel();
        return Auth::user()->can("create {$resourceName}");
    }

    public static function canEdit(Model $record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $resourceName = static::getModelLabel();
        return Auth::user()->can("edit {$resourceName}");
    }

    public static function canDelete(Model $record): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $resourceName = static::getModelLabel();
        return Auth::user()->can("delete {$resourceName}");
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
