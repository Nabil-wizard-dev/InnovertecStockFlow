<?php

namespace Database\Seeders;

use App\DTOs\Order\CreateOrderDTO;
use App\DTOs\Order\OrderLineDTO;
use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Interfaces\OrderServiceInterface;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function __construct(
        private readonly OrderServiceInterface $orders,
    ) {}

    /**
     * Seed 7 orders covering the whole lifecycle.
     *
     * Everything goes through OrderService rather than raw inserts, so the
     * seeded data cannot contradict the rules the API enforces: totals are
     * derived from the lines, unit prices are copied from the products, and
     * a confirmed order really did decrement the stock.
     */
    public function run(): void
    {
        $customers = Customer::orderBy('id')->get();
        $products = Product::orderBy('id')->get()->keyBy('product_reference');

        $blueprints = [
            // [reference, customer index, final status, [[product reference, quantity], ...]]
            [
                'ORD-2026-0001', 0, OrderStatus::Draft,
                [['REF-VIS-M6-40', 24]],
            ],
            [
                'ORD-2026-0002', 2, OrderStatus::Draft,
                [['REF-CHV-08', 150], ['REF-RON-M6', 40], ['REF-CSM-ADH-50', 6]],
            ],
            [
                'ORD-2026-0003', 1, OrderStatus::Confirmed,
                [['REF-OUT-PERC-850', 2], ['REF-ACC-FOR-19', 3]],
            ],
            [
                'ORD-2026-0004', 4, OrderStatus::Confirmed,
                [['REF-VIS-M8-60', 60], ['REF-ECR-M8', 60], ['REF-RON-G-M8', 60], ['REF-EPI-GNT-10', 12]],
            ],
            [
                'ORD-2026-0005', 6, OrderStatus::Confirmed,
                [['REF-OUT-VISS-18', 1]],
            ],
            [
                // Annulee alors qu'elle etait encore en brouillon : aucun
                // mouvement de stock n'a jamais eu lieu.
                'ORD-2026-0006', 3, OrderStatus::Cancelled,
                [['REF-OUT-NIV-60', 4], ['REF-OUT-MET-5', 10]],
            ],
            [
                // Confirmee PUIS annulee : le stock a ete decremente, puis
                // integralement restitue. Solde net nul.
                'ORD-2026-0007', 8, OrderStatus::Cancelled,
                [['REF-BLN-M10-80', 30], ['REF-ECR-M6', 100], ['REF-OUT-SCIE-1400', 2]],
            ],
        ];

        foreach ($blueprints as [$reference, $customerIndex, $finalStatus, $lines]) {
            $items = [];

            foreach ($lines as [$productReference, $quantity]) {
                $product = $products->get($productReference);

                if ($product === null) {
                    continue;
                }

                $items[] = new OrderLineDTO(
                    productPublicId: $product->public_id,
                    quantity: $quantity,
                );
            }

            if ($items === []) {
                continue;
            }

            $order = $this->orders->create(new CreateOrderDTO(
                customerPublicId: $customers[$customerIndex]->public_id,
                orderReference: $reference,
                status: OrderStatus::Draft,
                items: $items,
            ));

            // La commande 7 doit passer par la confirmation avant d'etre
            // annulee, pour que le mouvement de stock soit reellement joue
            // puis defait.
            if ($finalStatus === OrderStatus::Confirmed || $reference === 'ORD-2026-0007') {
                $order = $this->orders->confirm($order);
            }

            if ($finalStatus === OrderStatus::Cancelled) {
                $this->orders->cancel($order);
            }
        }
    }
}
