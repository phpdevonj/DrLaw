<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarModelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('car_models')->delete();
        
        $now = Carbon::now();

        $models = [
            'Acura-MDX', 'Acura-RDX', 'Acura-TLX', 'Acura-Integra', 'Alfa Romeo-Giulia', 'Alfa Romeo-Stelvio', 'Alfa Romeo-Tonale', 'Audi-A3', 'Audi-A4', 'Audi-A5', 'Audi-A6', 'Audi-A7', 'Audi-A8', 'Audi-Q3', 'Audi-Q5', 'Audi-Q7', 'Audi-Q8', 'Audi-e-tron', 'Audi-e-tron GT', 'BMW-2 Series', 'BMW-3 Series', 'BMW-4 Series', 'BMW-5 Series', 'BMW-7 Series', 'BMW-8 Series', 'BMW-X1', 'BMW-X3', 'BMW-X5', 'BMW-X7', 'BMW-Z4', 'BMW-i4', 'BMW-i5', 'BMW-i7', 'BMW-iX', 'Buick-Enclave', 'Buick-Encore', 'Buick-Encore GX', 'Buick-Envision', 'Cadillac-CT4', 'Cadillac-CT5', 'Cadillac-Escalade', 'Cadillac-XT4', 'Cadillac-XT5', 'Cadillac-XT6', 'Cadillac-Celestiq', 'Cadillac-Lyriq', 'Chevrolet-Blazer', 'Chevrolet-Camaro', 'Chevrolet-Colorado', 'Chevrolet-Corvette', 'Chevrolet-Equinox', 'Chevrolet-Malibu', 'Chevrolet-Silverado 1500', 'Chevrolet-Suburban', 'Chevrolet-Tahoe', 'Chevrolet-Traverse', 'Chevrolet-Trax', 'Chevrolet-Bolt EV/EUV', 'Chrysler-300', 'Chrysler-Pacifica', 'Dodge-Charger', 'Dodge-Challenger', 'Dodge-Durango', 'Dodge-Hornet', 'Ford-Bronco', 'Ford-Bronco Sport', 'Ford-Escape', 'Ford-Edge', 'Ford-Expedition', 'Ford-Explorer', 'Ford-F-150', 'Ford-F-150 Lightning', 'Ford-Maverick', 'Ford-Mustang', 'Ford-Ranger', 'Ford-Transit', 'Genesis-G70', 'Genesis-G80', 'Genesis-G90', 'Genesis-GV60', 'Genesis-GV70', 'Genesis-GV80', 'GMC-Acadia', 'GMC-Canyon', 'GMC-Hummer EV', 'GMC-Sierra 1500', 'GMC-Terrain', 'GMC-Yukon', 'Honda-Accord', 'Honda-Civic', 'Honda-CR-V', 'Honda-HR-V', 'Honda-Odyssey', 'Honda-Pilot', 'Honda-Passport', 'Honda-Ridgeline', 'Hyundai-Elantra', 'Hyundai-Ioniq 5', 'Hyundai-Kona', 'Hyundai-Palisade', 'Hyundai-Santa Fe', 'Hyundai-Sonata', 'Hyundai-Tucson', 'Hyundai-Venue', 'Infiniti-Q50', 'Infiniti-QX50', 'Infiniti-QX55', 'Infiniti-QX60', 'Infiniti-QX80', 'Jaguar-F-PACE', 'Jaguar-I-PACE', 'Jaguar-E-PACE', 'Jaguar-XF', 'Jaguar-F-TYPE', 'Jeep-Compass', 'Jeep-Grand Cherokee', 'Jeep-Grand Wagoneer', 'Jeep-Renegade', 'Jeep-Wagoneer', 'Jeep-Wrangler', 'Jeep-Gladiator', 'Kia-Carnival', 'Kia-Forte', 'Kia-K5', 'Kia-Niro', 'Kia-Seltos', 'Kia-Sorento', 'Kia-Soul', 'Kia-Sportage', 'Kia-Telluride', 'Kia-EV6', 'Kia-EV9', 'Land Rover-Defender', 'Land Rover-Discovery', 'Land Rover-Range Rover', 'Land Rover-Range Rover Evoque', 'Land Rover-Range Rover Sport', 'Lexus-ES', 'Lexus-GX', 'Lexus-IS', 'Lexus-LC', 'Lexus-LS', 'Lexus-LX', 'Lexus-NX', 'Lexus-RX', 'Lexus-UX', 'Lincoln-Aviator', 'Lincoln-Corsair', 'Lincoln-Nautilus', 'Lincoln-Navigator', 'Mazda-CX-30', 'Mazda-CX-5', 'Mazda-CX-50', 'Mazda-CX-90', 'Mazda-Mazda3', 'Mazda-MX-5 Miata', 'Mercedes-Benz-A-Class', 'Mercedes-Benz-C-Class', 'Mercedes-Benz-CLA', 'Mercedes-Benz-E-Class', 'Mercedes-Benz-G-Class', 'Mercedes-Benz-S-Class', 'Mercedes-Benz-GLA', 'Mercedes-Benz-GLC', 'Mercedes-Benz-GLE', 'Mercedes-Benz-GLS', 'Mercedes-Benz-EQB', 'Mercedes-Benz-EQS', 'Mini-Cooper', 'Mini-Countryman', 'Mini-Hardtop', 'Mitsubishi-Eclipse Cross', 'Mitsubishi-Mirage', 'Mitsubishi-Outlander', 'Mitsubishi-Outlander Sport', 'Nissan-Altima', 'Nissan-Ariya', 'Nissan-Frontier', 'Nissan-Kicks', 'Nissan-Leaf', 'Nissan-Murano', 'Nissan-Pathfinder', 'Nissan-Rogue', 'Nissan-Sentra', 'Nissan-Titan', 'Nissan-Versa', 'Porsche-718 Boxster', 'Porsche-718 Cayman', 'Porsche-911', 'Porsche-Cayenne', 'Porsche-Macan', 'Porsche-Panamera', 'Porsche-Taycan', 'Ram-1500', 'Ram-2500', 'Ram-3500', 'Subaru-Ascent', 'Subaru-BRZ', 'Subaru-Crosstrek', 'Subaru-Forester', 'Subaru-Impreza', 'Subaru-Legacy', 'Subaru-Outback', 'Subaru-Solterra', 'Subaru-WRX', 'Tesla-Model 3', 'Tesla-Model S', 'Tesla-Model X', 'Tesla-Model Y', 'Tesla-Cybertruck', 'Toyota-4Runner', 'Toyota-Camry', 'Toyota-Corolla', 'Toyota-Crown', 'Toyota-Highlander', 'Toyota-Mirai', 'Toyota-Prius', 'Toyota-RAV4', 'Toyota-Sequoia', 'Toyota-Sienna', 'Toyota-Tacoma', 'Toyota-Tundra', 'Toyota-Venza', 'Toyota-Supra', 'Volkswagen-Atlas', 'Volkswagen-Atlas Cross Sport', 'Volkswagen-Golf', 'Volkswagen-ID.4', 'Volkswagen-Jetta', 'Volkswagen-Tiguan', 'Volvo-C40', 'Volvo-S60', 'Volvo-S90', 'Volvo-V60', 'Volvo-XC40', 'Volvo-XC60', 'Volvo-XC90'
        ];

        $insertData = [];
        foreach ($models as $model) {
            $insertData[] = [
                'name' => $model,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('car_models')->insert($insertData);
    }
}
