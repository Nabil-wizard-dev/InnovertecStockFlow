<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed 10 customers covering the three possible statuses.
     */
    public function run(): void
    {
        $customers = [
            // [first name, last name, email, phone, address, status]
            ['Amina', 'Traore', 'amina.traore@example.com', '+225 07 11 22 33', 'Cocody, Riviera 3, Abidjan', CustomerStatus::Active],
            ['Kouadio', 'N\'Guessan', 'kouadio.nguessan@example.com', '+225 05 44 55 66', 'Yopougon, Selmer, Abidjan', CustomerStatus::Active],
            ['Fatou', 'Diallo', 'fatou.diallo@example.com', '+225 01 77 88 99', 'Plateau, Rue du Commerce, Abidjan', CustomerStatus::Active],
            ['Ibrahim', 'Kone', 'ibrahim.kone@example.com', '+225 07 23 45 67', 'Marcory Zone 4, Abidjan', CustomerStatus::Active],
            ['Ange', 'Kouassi', 'ange.kouassi@example.com', '+225 05 98 76 54', 'Treichville, Avenue 16, Abidjan', CustomerStatus::Active],
            ['Mariam', 'Cisse', 'mariam.cisse@example.com', '+225 01 32 10 98', 'Bouake, Quartier Commerce', CustomerStatus::Active],
            ['Yao', 'Brou', 'yao.brou@example.com', '+225 07 65 43 21', 'Adjame, Liberte, Abidjan', CustomerStatus::Active],
            ['Sekou', 'Bamba', 'sekou.bamba@example.com', '+225 05 11 09 87', 'San Pedro, Zone portuaire', CustomerStatus::Inactive],
            ['Nadege', 'Yeboua', 'nadege.yeboua@example.com', '+225 01 55 66 77', 'Daloa, Quartier Tazibouo', CustomerStatus::Inactive],
            ['Etienne', 'Zadi', 'etienne.zadi@example.com', null, null, CustomerStatus::Suspended],
        ];

        foreach ($customers as [$firstName, $lastName, $email, $phone, $address, $status]) {
            Customer::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'telephone' => $phone,
                'adresse' => $address,
                'status' => $status,
            ]);
        }
    }
}
