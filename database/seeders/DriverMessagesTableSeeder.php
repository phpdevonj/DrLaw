<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DriverMessage;

class DriverMessagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DriverMessage::where('language_code', 'en')->delete();
        
        DriverMessage::insert([
            [
                'language_code' => 'en',
                'message_type' => 'pick_up',
                'message_key' => 'arrival',
                'message_value' => 'Hi, I’m here in a [%%car_color%%, %%car_model%%]!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'pick_up',
                'message_key' => 'eta',
                'message_value' => 'Hi, I’m [%%x%%] minutes away. See you soon!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'pick_up',
                'message_key' => 'location_check',
                'message_value' => 'Hi, I’m at [%%specific_location%%].',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'pick_up',
                'message_key' => 'delay',
                'message_value' => 'Hi, I’m in a bit of traffic. I’ll be there in [%%x%%] minutes.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'post_ride',
                'message_key' => 'lost_item',
                'message_value' => 'Hi, I think you left something in the car. Contact support to arrange pickup!',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'special_situations',
                'message_key' => 'cancellation',
                'message_value' => 'Hi, I’ve been waiting. Should I cancel?',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'rider_cancellation_reasons',
                'message_key' => 'change_of_plans',
                'message_value' => 'Change of Plans',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'rider_cancellation_reasons',
                'message_key' => 'driver_delayed',
                'message_value' => 'Driver Delayed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'rider_cancellation_reasons',
                'message_key' => 'location_issue',
                'message_value' => 'Location issue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'rider_cancellation_reasons',
                'message_key' => 'found_alternative_transport',
                'message_value' => 'Found Alternative Transport',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'rider_cancellation_reasons',
                'message_key' => 'safety_concerns',
                'message_value' => 'Safety concerns',
                'created_at' => now(),
                'updated_at' => now(),
            ],            
            [
                'language_code' => 'en',
                'message_type' => 'driver_cancellation_reasons',
                'message_key' => 'passenger_no_show',
                'message_value' => 'Passenger No-Show',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'driver_cancellation_reasons',
                'message_key' => 'ride_location_issue',
                'message_value' => 'Ride Location Issue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'driver_cancellation_reasons',
                'message_key' => 'vehicle_issue',
                'message_value' => 'Vehichle Issue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'driver_cancellation_reasons',
                'message_key' => 'passenger_behavior_concern',
                'message_value' => 'Passenger Behavior Concern',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'driver_cancellation_reasons',
                'message_key' => 'personal_emergency',
                'message_value' => 'Personal Emergency',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'general_cancellation_options',
                'message_key' => 'other',
                'message_value' => 'Other',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'early_completion_reasons',
                'message_key' => ' the_rider_requested_to_end_the_trip',
                'message_value' => ' The rider requested to end the trip',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'early_completion_reasons',
                'message_key' => 'safety_concern',
                'message_value' => 'Safety concern',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'early_completion_reasons',
                'message_key' => 'vehicle_issue',
                'message_value' => 'Vehicle issue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'early_completion_reasons',
                'message_key' => 'rider_behavior_issue',
                'message_value' => 'Rider behavior issue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'language_code' => 'en',
                'message_type' => 'early_completion_reasons',
                'message_key' => 'other',
                'message_value' => 'Other',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
