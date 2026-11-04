<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Support\PermissionHelper;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($supplier);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->create($user) && $this->belongsToCurrentBusiness($supplier);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->update($user, $supplier);
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->update($user, $supplier);
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(Supplier $supplier): bool
    {
        return currentBusiness()?->id === $supplier->business_id;
    }
}
