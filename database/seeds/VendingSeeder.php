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
        Vending::create([
            'name' => 'aft_kosayu',
        ]);
    }
}
