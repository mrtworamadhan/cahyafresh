<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PoBatch;
use Illuminate\Auth\Access\HandlesAuthorization;

class PoBatchPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PoBatch');
    }

    public function view(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('View:PoBatch');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PoBatch');
    }

    public function update(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('Update:PoBatch');
    }

    public function delete(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('Delete:PoBatch');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:PoBatch');
    }

    public function restore(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('Restore:PoBatch');
    }

    public function forceDelete(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('ForceDelete:PoBatch');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:PoBatch');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:PoBatch');
    }

    public function replicate(AuthUser $authUser, PoBatch $poBatch): bool
    {
        return $authUser->can('Replicate:PoBatch');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PoBatch');
    }

}