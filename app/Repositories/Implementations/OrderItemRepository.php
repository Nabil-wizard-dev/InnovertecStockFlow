<?php

namespace App\Repositories\Implementations;

use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderItemRepository implements OrderItemRepositoryInterface
{
    public function find($id)
    {
        return OrderItem::find($id);
    }

    public function all()
    {
        return OrderItem::all();
    }

    /**
     * Get a paginated list of records.
     *
     * @return LengthAwarePaginator<int, OrderItem>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return OrderItem::query()->latest()->paginate($perPage);
    }

    public function create(array $data)
    {
        return OrderItem::create($data);
    }

    public function update($id, array $data)
    {
        $orderItem = OrderItem::find($id);
        if ($orderItem) {
            $orderItem->update($data);

            return $orderItem;
        }

        return null;
    }

    public function delete($id)
    {
        $orderItem = OrderItem::find($id);
        if ($orderItem) {
            $orderItem->delete();

            return true;
        }

        return false;
    }
}
