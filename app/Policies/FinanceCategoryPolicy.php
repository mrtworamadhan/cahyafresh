<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FinanceCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class FinanceCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FinanceCategory');
    }

    public function view(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('View:FinanceCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FinanceCategory');
    }

    public function update(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('Update:FinanceCategory');
    }

    public function delete(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('Delete:FinanceCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FinanceCategory');
    }

    public function restore(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('Restore:FinanceCategory');
    }

    public function forceDelete(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('ForceDelete:FinanceCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FinanceCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FinanceCategory');
    }

    public function replicate(AuthUser $authUser, FinanceCategory $financeCategory): bool
    {
        return $authUser->can('Replicate:FinanceCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FinanceCategory');
    }

}