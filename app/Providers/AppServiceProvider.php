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
use App\Services\Interfaces\CustomerServiceInterface;
use App\Services\Implementations\CustomerService;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\Implementations\OrderService;
use App\Services\Interfaces\OrderItemServiceInterface;
use App\Services\Implementations\OrderItemService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderItemRepositoryInterface::class, OrderItemRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        // Services
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(OrderItemServiceInterface::class, OrderItemService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
