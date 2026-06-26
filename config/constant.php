<?php
return [
    'IMAGE_EXTENTIONS' => ['png','jpg','jpeg','gif'],
    'PER_PAGE_LIMIT' => 10,
    'MAIL_SETTING' => [
        'MAIL_MAILER' => env('MAIL_MAILER'),
        'MAIL_HOST' => env('MAIL_HOST'),
        'MAIL_PORT' => env('MAIL_PORT'),
        'MAIL_USERNAME' => env('MAIL_USERNAME'),
        'MAIL_PASSWORD' => env('MAIL_PASSWORD'),
        'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
        'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
    ],
    'MAIL_PLACEHOLDER' => [
        'MAIL_MAILER' => 'smtp',
        'MAIL_HOST' => 'smtp.gmail.com',
        'MAIL_PORT' => '587',
        'MAIL_ENCRYPTION' => 'tls',
        'MAIL_USERNAME' => 'youremail@gmail.com',
        'MAIL_PASSWORD' => 'Password',
        'MAIL_FROM_ADDRESS' => 'youremail@gmail.com',
    ],
    'PAYMENT_GATEWAY_SETTING' => [
        // 'cash' => [],
        'stripe' => [ 'url', 'secret_key', 'publishable_key' ],
        //'razorpay' => [ 'key_id', 'secret_id' ],
        'paystack' => [ 'public_key', 'secret_key' ],
        'mobile_money' => [ 'url', 'secret_key', 'publishable_key' ],
        //'flutterwave' => [ 'public_key', 'secret_key', 'encryption_key' ],
        'paypal' => [  'client_id', 'client_secret' ],
        //'paytabs' => [ 'client_key', 'profile_id', 'server_key'],
        //'mercadopago' => [ 'public_key', 'access_token' ],
        //'myfatoorah' => ['access_token'],
        //'paytm' => [ 'merchant_id', 'merchant_key' ],
        //'pesapal' => [ 'consumer_key', 'consumer_secret' ],
    ],

    'wallet' => [
        'min_amount_to_add'     => '',
        'max_amount_to_add'     => '',
        'min_amount_to_get_ride'=> '',
        'preset_topup_amount'   => '',
    ],

    'ride' => [
        'max_time_for_find_drivers_for_regular_ride_in_minute'  => '',
        'ride_accept_decline_duration_for_driver_in_second'     => '',
        // 'schedule_ride_after_minute'    => '',
        // 'min_time_for_find_driver_for_schedule_ride_in_minute'  => '',
        'preset_tip_amount'   => '',
        'apply_additional_fee'  => '',
        'surge_price'  => '',
        // 'is_bidding'  => '',
        'loyalty_program'  => '',
        'point_ratio'  => '',
        'point_value'  => '',
        'min_points_withdrawal'  => '',
        'rider_rating' => '',
        'rider_rating_min_completed_rides' => '', 
        'driver_rating' => '',
        'driver_rating_min_completed_rides' => '', 
        'is_call_enabled' => '',
        'normal_ride_restriction_buffer' => '',
        'scheduled_ride_restriction_buffer' => '',
        'increase_income_url' => '',
        'ride_accept_button_distance' => '',
        'completed_before_destination_distance' => '',        
    ],
    'ride_status' => ['new_ride_requested', 'no_drivers_available', 'accepted', 'arriving', 'arrived', 'in_progress', 'canceled', 'completed' , 'sos'],
    'notification' => [
        'IS_ONESIGNAL' => '',
        // 'IS_FIREBASE' => '',
    ],
    
    'app_info' => [ 
        'app_name' => '', 'image_title' => '', 'background_image' => '', 'logo_image' => '',
    ],

    'our_mission' => [
        'title' => '',
        'image' => '',
    ],

    'download_app' => [
        'title' => '',
        'subtitle' => '',
        'image' => '',
        'play_store' => '',
        'app_store' => ''
    ],

    'contactus_info' => [
        'about_title' => '',
        'image' => '',
    ],

    'client_testimonials' => [
        'title' => '',
        'subtitle' => '',
        'image' => '',
    ],

    'why_choose' => [
        'title' => '',
        'subtitle' => '',
        'image' => '',
    ],

    'referral' => [
        'referral_bonus_points'     => '',
        'referral_reward_condition'     => '',
        'unique_device_id'     => '',
        'referral_max_limit'     => '',
    ],
];