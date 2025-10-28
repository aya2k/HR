<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\Governorate;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        $file = database_path('seeders/data/cities.json');
        if (!file_exists($file)) {
            $this->command->error("File not found: $file");
            return;
        }

        $cities = json_decode(file_get_contents($file), true);

        foreach ($cities as $city) {
            $gov = Governorate::where('governorate_name_en', $city['governorate_name_en'])->first();
            if (!$gov) continue;

            City::updateOrCreate(
                [
                    'governorate_id' => $gov->id,
                    'city_name_ar' => $city['city_name_ar']
                ],
                [
                    'city_name_en' => $city['city_name_en'] ?? null
                ]
            );
        }

        $this->command->info("✅ Cities imported successfully!");
    }
    }

