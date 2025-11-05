<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run()
    {
       
    
        $path = database_path('seeders/data/cities.json');

        if (!File::exists($path)) {
            $this->command->error("❌ cities.json file not found!");
            return;
        }

        $json = json_decode(File::get($path), true);

        // نبحث عن العنصر اللي نوعه table
        $table = collect($json)->firstWhere('type', 'table');

        if (!$table || empty($table['data'])) {
            $this->command->warn("⚠️ No data found in JSON.");
            return;
        }

        foreach ($table['data'] as $city) {
            City::updateOrCreate(
                ['id' => $city['id']],
                [
                    'governorate_id' => $city['governorate_id'] ?? null,
                    'name_en' => $city['name'] ?? '',
                    'name_ar' => $city['name'] ?? '',
                ]
            );
        }

        $this->command->info("✅ CitySeeder completed successfully! Total cities: " . count($table['data']));
    }
    }

