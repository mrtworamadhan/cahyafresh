<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Ledger;
use Illuminate\Auth\Access\HandlesAuthorization;

class LedgerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ledger');
    }

    public function view(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('View:Ledger');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ledger');
    }

    public function update(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('Update:Ledger');
    }

    public function delete(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('Delete:Ledger');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Ledger');
    }

    public function restore(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('Restore:Ledger');
    }

    public function forceDelete(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('ForceDelete:Ledger');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ledger');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ledger');
    }

    public function replicate(AuthUser $authUser, Ledger $ledger): bool
    {
        return $authUser->can('Replicate:Ledger');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ledger');
    }

}