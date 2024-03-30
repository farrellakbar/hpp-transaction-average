<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataAwalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('hpp_transactions')->insert([
            'description' => 'Pembelian',
            'date' => '2021-01-01',
            'qty' => 40,
            'cost' => 100,
            'price' => 100,
            'total_cost' => 4000,
            'qty_balance' => 40,
            'value_balance' => 4000,
            'hpp' => 100,
            'created_at' => now(),
        ]);
    }
}
