<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Contracts\Services\Auth\AuthServiceInterface;
use App\Contracts\Services\User\UserServiceInterface;
use App\Contracts\Services\Auth\RateLimitServiceInterface;
use App\Contracts\Services\Auth\TokenServiceInterface;
use App\Contracts\Services\Product\ProductServiceInterface;
use App\Contracts\Services\Product\CategoryServiceInterface;
use App\Contracts\Services\Farmer\FarmerServiceInterface;
use App\Contracts\Services\Transaction\TransactionServiceInterface;
use App\Contracts\Services\Debt\DebtServiceInterface;
use App\Contracts\Services\Repayment\RepaymentServiceInterface;

use App\Services\Auth\AuthService;
use App\Services\User\UserService;
use App\Services\Auth\RateLimitService;
use App\Services\Auth\TokenService;
use App\Services\Product\ProductService;
use App\Services\Product\CategoryService;
use App\Services\Farmer\FarmerService;
use App\Services\Transaction\TransactionService;
use App\Services\Debt\DebtService;
use App\Services\Repayment\RepaymentService;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RateLimitServiceInterface::class, RateLimitService::class);
        $this->app->bind(TokenServiceInterface::class, TokenService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(FarmerServiceInterface::class, FarmerService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(DebtServiceInterface::class, DebtService::class);
        $this->app->bind(RepaymentServiceInterface::class, RepaymentService::class);
    }

    public function boot(): void
    {

    }
}
