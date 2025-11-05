<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
      
        $path = database_path('seeders/data/countries.json');
        $jsonData = json_decode(File::get($path), true);

        // نجيب العنصر اللي type = "table"
        $tableData = collect($jsonData)->firstWhere('type', 'table');

        if ($tableData && isset($tableData['data'])) {
            foreach ($tableData['data'] as $country) {
                Country::updateOrCreate(
                    ['id' => $country['id']],
                    ['name_en' => $country['name']]
                );
            }
        }
    }
}
