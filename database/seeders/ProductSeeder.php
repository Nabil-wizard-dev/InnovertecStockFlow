<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed a realistic hardware catalogue of 20 products.
     *
     * Statuses and quantities are deliberately decorrelated: `status` carries
     * the commercial intent, `available_quantity` the physical stock. The
     * archived ladder still has 3 units, and the out of stock grinder has 0 —
     * both situations are legitimate under the current design.
     */
    public function run(): void
    {
        $catalogue = [
            // [name, reference, unit price, quantity, status]
            ['Vis tete fraisee M6x40 inox', 'REF-VIS-M6-40', 12.50, 480, ProductStatus::Available],
            ['Vis tete fraisee M8x60 inox', 'REF-VIS-M8-60', 18.90, 320, ProductStatus::Available],
            ['Ecrou hexagonal M6 inox', 'REF-ECR-M6', 3.25, 1200, ProductStatus::Available],
            ['Ecrou hexagonal M8 inox', 'REF-ECR-M8', 4.10, 950, ProductStatus::Available],
            ['Rondelle plate M6 (sachet de 100)', 'REF-RON-M6', 1.15, 2400, ProductStatus::Available],
            ['Rondelle Grower M8 (sachet de 100)', 'REF-RON-G-M8', 1.80, 1750, ProductStatus::Available],
            ['Cheville a expansion 8 mm', 'REF-CHV-08', 0.95, 3000, ProductStatus::Available],
            ['Boulon TH M10x80 galvanise', 'REF-BLN-M10-80', 24.00, 210, ProductStatus::Available],
            ['Perceuse a percussion 850 W', 'REF-OUT-PERC-850', 189.90, 24, ProductStatus::Available],
            ['Visseuse sans fil 18 V', 'REF-OUT-VISS-18', 249.00, 15, ProductStatus::Available],
            ['Meuleuse d\'angle 125 mm', 'REF-OUT-MEUL-125', 134.50, 0, ProductStatus::OutOfStock],
            ['Scie circulaire 1400 W', 'REF-OUT-SCIE-1400', 219.00, 7, ProductStatus::Available],
            ['Jeu de forets HSS 19 pieces', 'REF-ACC-FOR-19', 42.75, 88, ProductStatus::Available],
            ['Disque a tronconner 125 mm (x10)', 'REF-ACC-DSQ-125', 15.60, 0, ProductStatus::AwaitingRestock],
            ['Ruban adhesif toile 50 m', 'REF-CSM-ADH-50', 6.40, 540, ProductStatus::Available],
            ['Metre ruban 5 m', 'REF-OUT-MET-5', 11.20, 130, ProductStatus::Available],
            ['Niveau a bulle 60 cm', 'REF-OUT-NIV-60', 27.90, 46, ProductStatus::Available],
            ['Gants de manutention taille 10', 'REF-EPI-GNT-10', 8.75, 300, ProductStatus::Available],
            ['Casque de chantier blanc', 'REF-EPI-CSQ-B', 19.50, 0, ProductStatus::AwaitingRestock],
            ['Escabeau alu 5 marches (ancien modele)', 'REF-ESC-ALU-5', 89.00, 3, ProductStatus::Archived],
        ];

        foreach ($catalogue as [$name, $reference, $price, $quantity, $status]) {
            Product::create([
                'name' => $name,
                'description' => "Reference catalogue {$reference}.",
                'product_reference' => $reference,
                'unit_price' => $price,
                'available_quantity' => $quantity,
                'status' => $status,
            ]);
        }
    }
}
