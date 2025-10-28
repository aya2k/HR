<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = File::get(database_path('seeders/data/governorates.json'));
        $governorates = json_decode($json, true)['data'];

        foreach ($governorates as $gov) {
            DB::table('governorates')->insert([
                'id' => $gov['id'],
                'governorate_name_ar' => $gov['governorate_name_ar'],
                'governorate_name_en' => $gov['governorate_name_en'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
