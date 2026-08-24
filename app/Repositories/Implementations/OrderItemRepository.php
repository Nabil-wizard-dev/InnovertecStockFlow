<?php

namespace App\Repositories\Implementations;

use App\Models\OrderItem;
use App\Repositories\Interfaces\OrderItemRepositoryInterface;

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
