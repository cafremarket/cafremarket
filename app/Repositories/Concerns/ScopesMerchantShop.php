<?php

namespace App\Repositories\Concerns;

use Illuminate\Support\Facades\Auth;

trait ScopesMerchantShop
{
    protected function shouldScopeToMerchantShop(): bool
    {
        $user = Auth::user();

        return $user && $user->isFromMerchant() && ! $user->isFromPlatform();
    }

    protected function merchantScopedQuery()
    {
        $query = $this->model->newQuery();

        if ($this->shouldScopeToMerchantShop()) {
            $query->mine();
        }

        return $query;
    }

    public function find($id)
    {
        return $this->merchantScopedQuery()->findOrFail($id);
    }

    public function findTrash($id)
    {
        return $this->merchantScopedQuery()->onlyTrashed()->findOrFail($id);
    }
}
