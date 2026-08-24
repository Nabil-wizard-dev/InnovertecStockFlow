<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Repositories\Implementations\ProductRepository;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Repositories\Implementations\OrderRepository;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use App\Repositories\Implementations\OrderItemRepository;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\Implementations\CustomerRepository;
use App\Services\Interfaces\ProductServiceInterface;
use App\Services\Implementations\ProductService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderItemRepositoryInterface::class, OrderItemRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        $this->app->bind(ProductServiceInterface::class, ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
