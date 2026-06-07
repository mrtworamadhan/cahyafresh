<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Courier;
use Illuminate\Auth\Access\HandlesAuthorization;

class CourierPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Courier');
    }

    public function view(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('View:Courier');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Courier');
    }

    public function update(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('Update:Courier');
    }

    public function delete(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('Delete:Courier');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Courier');
    }

    public function restore(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('Restore:Courier');
    }

    public function forceDelete(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('ForceDelete:Courier');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Courier');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Courier');
    }

    public function replicate(AuthUser $authUser, Courier $courier): bool
    {
        return $authUser->can('Replicate:Courier');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Courier');
    }

}