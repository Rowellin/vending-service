<?php

use App\Models\Vending;
use Illuminate\Database\Seeder;

class VendingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i < 6; $i++) {
            Vending::create([
                'name' => 'Vending ' . $i,
            ]);
        }
    }
}
