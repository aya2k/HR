<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Governorate;

class GovernorateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $path = database_path('seeders/data/governorates.json');
        $governorates = json_decode(File::get($path), true);
           $tableData = collect($governorates)->firstWhere('type', 'table');
        foreach ($tableData['data'] as $gov) {
            Governorate::updateOrCreate(
                ['id' => $gov['id']],
                [
                    'country_id' => $gov['country_id'],
                    'name_en' => $gov['name'],
                    'name_ar' => $gov['name'],
                ]
            );
        }


        

        

    }
}
