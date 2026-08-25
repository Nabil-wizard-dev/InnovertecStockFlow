<?php 
namespace App\Repositories\Implementations;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function find($id)
    {
        return Order::find($id);
    }

    public function all()
    {
        return Order::all();
    }

    /**
     * Get a paginated list of records.
     *
     * @return LengthAwarePaginator<int, Order>
     */
    public function paginate(int $perPage = 15, ?OrderStatus $status = null): LengthAwarePaginator
    {
        return Order::query()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Order::create($data);
    }

    public function update($id, array $data)
    {
        $order = Order::find($id);
        if ($order) {
            $order->update($data);
            return $order;
        }
        return null;
    }

    public function delete($id)
    {
        $order = Order::find($id);
        if ($order) {
            $order->delete();
            return true;
        }
        return false;
    }
}