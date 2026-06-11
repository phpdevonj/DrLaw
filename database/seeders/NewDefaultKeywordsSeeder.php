<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Screen;
use App\Models\DefaultKeyword;

class NewDefaultKeywordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $screen_data = [

            [
                "screenID" => "1",
                "ScreenName" => "WalkThroughScreen",
                "keyword_data" => [
                    [
                        "screenId" => "1",
                        "keyword_id" => 401,
                        "keyword_name" => "getStarted",
                        "keyword_value" => "Get Started"
                    ],
                ]
            ],

            [
                "screenID" => "2",
                "ScreenName" => "Register Screen",
                "keyword_data" => [
                    [
                        "screenId" => "2",
                        "keyword_id" => 413,
                        "keyword_name" => "signUpToGetStarted",
                        "keyword_value" => "Sign up to get Started"
                    ],
                    [
                        "screenId" => "2",
                        "keyword_id" => 414,
                        "keyword_name" => "termsAndConditions",
                        "keyword_value" => "Terms & Conditions"
                    ],
                    [
                        "screenId" => "2",
                        "keyword_id" => 415,
                        "keyword_name" => "signIn",
                        "keyword_value" => "Sign In"
                    ],
                ]
            ],

            [
                "screenID" => "3",
                "ScreenName" => "Login Screen",
                "keyword_data" => [
                    [
                        "screenId" => "3",
                        "keyword_id" => 403,
                        "keyword_name" => "welcomeBack",
                        "keyword_value" => "Welcome Back"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 404,
                        "keyword_name" => "signInToContinue",
                        "keyword_value" => "Sign In to Continue"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 407,
                        "keyword_name" => "signInUsingYourMobileNumberSubtitle",
                        "keyword_value" => "We'll send you a one-time code to verify your number"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 408,
                        "keyword_name" => "verifyYourNumber",
                        "keyword_value" => "Verify your Number"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 409,
                        "keyword_name" => "enterThe6DigitCodeWeVeSentByTextTo",
                        "keyword_value" => "Enter the 6 digit code we’ve sent by text to"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 410,
                        "keyword_name" => "didNotReceiveTheCode",
                        "keyword_value" => "Didn’t receive the code?"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 411,
                        "keyword_name" => "reSend",
                        "keyword_value" => "Re-send"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 412,
                        "keyword_name" => "verifyOTP",
                        "keyword_value" => "Verify OTP"
                    ],
                    [
                        "screenId" => "3",
                        "keyword_id" => 454,
                        "keyword_name" => "enter_your_mobile_number_to_receive_a_verification_code",
                        "keyword_value" => "Enter your mobile number to receive a verification code"
                    ],
                ]
            ],

            [
                "screenID" => "4",
                "ScreenName" => "Forget Password Screen",
                "keyword_data" => [
                    [
                        "screenId" => "4",
                        "keyword_id" => 405,
                        "keyword_name" => "forgotPasswordSubtitle",
                        "keyword_value" => "Don't worry; even the best of us misplace things sometimes. Let's get you back in!"
                    ],
                    [
                        "screenId" => "4",
                        "keyword_id" => 406,
                        "keyword_name" => "send",
                        "keyword_value" => "Send"
                    ],
                ]
            ],

            [
                "screenID" => "5",
                "ScreenName" => "Dashboard Screen",
                "keyword_data" => [
                    [
                        "screenId" => "5",
                        "keyword_id" => 445,
                        "keyword_name" => "upcomingRideRequest",
                        "keyword_value" => "Upcoming Ride Request"
                    ],
                    [
                        "screenId" => "5",
                        "keyword_id" => 446,
                        "keyword_name" => "youHavePendingRideRequests",
                        "keyword_value" => "You have pending ride requests"
                    ],
                    [
                        "screenId" => "5",
                        "keyword_id" => 447,
                        "keyword_name" => "noDataFound",
                        "keyword_value" => "No Data Found"
                    ],
                    [
                        "screenId" => "5",
                        "keyword_id" => 449,
                        "keyword_name" => "weAreSorry",
                        "keyword_value" => "We are sorry,"
                    ],
                    [
                        "screenId" => "5",
                        "keyword_id" => 456,
                        "keyword_name" => "sample_text",
                        "keyword_value" => "Sample Text"
                    ],
                ]
            ],

            [
                "screenID" => "7",
                "ScreenName" => "Profile Screen",
                "keyword_data" => [
                    [
                        "screenId" => "7",
                        "keyword_id" => 416,
                        "keyword_name" => "streetAddress",
                        "keyword_value" => "Street Address:"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 417,
                        "keyword_name" => "town",
                        "keyword_value" => "Town:"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 418,
                        "keyword_name" => "city",
                        "keyword_value" => "City:"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 419,
                        "keyword_name" => "postCode",
                        "keyword_value" => "Post Code:"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 420,
                        "keyword_name" => "country",
                        "keyword_value" => "Country:"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 430,
                        "keyword_name" => "addressType",
                        "keyword_value" => "Address Type"
                    ],
                    [
                        "screenId" => "7",
                        "keyword_id" => 431,
                        "keyword_name" => "customAddressType",
                        "keyword_value" => "Custom Address Type"
                    ],
                ]
            ],

            [
                "screenID" => "9",
                "ScreenName" => "Ride Details Screen",
                "keyword_data" => [
                    [
                        "screenId" => "9",
                        "keyword_id" => 450,
                        "keyword_name" => "confirmYourBooking",
                        "keyword_value" => "Confirm Your Booking"
                    ],
                    [
                        "screenId" => "9",
                        "keyword_id" => 451,
                        "keyword_name" => "BookingIsReady",
                        "keyword_value" => "Your ride is ready to be booked. Do you want to proceed?"
                    ],
                ]
            ],

            [
                "screenID" => "25",
                "ScreenName" => "Ride Status Screen",
                "keyword_data" => [
                    [
                        "screenId" => "25",
                        "keyword_id" => 402,
                        "keyword_name" => "coupon_subtitle",
                        "keyword_value" => "You can enter or Select Promo code"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 421,
                        "keyword_name" => "promoCode",
                        "keyword_value" => "Promo Code"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 422,
                        "keyword_name" => "applied",
                        "keyword_value" => "Applied"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 423,
                        "keyword_name" => "apply",
                        "keyword_value" => "Apply"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 424,
                        "keyword_name" => "callDriver",
                        "keyword_value" => "Call Driver"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 425,
                        "keyword_name" => "whyDoYouWantToCancelTheRide",
                        "keyword_value" => "Why do you want to cancel the ride?"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 427,
                        "keyword_name" => "discount",
                        "keyword_value" => "Discount"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 428,
                        "keyword_name" => "upcoming",
                        "keyword_value" => "Upcoming"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 429,
                        "keyword_name" => "history",
                        "keyword_value" => "History"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 453,
                        "keyword_name" => "schedule_ride",
                        "keyword_value" => "Schedule Ride"
                    ],
                ]
            ],

            [
                "screenID" => "27",
                "ScreenName" => "Review Screen",
                "keyword_data" => [
                    [
                        "screenId" => "27",
                        "keyword_id" => 426,
                        "keyword_name" => "addCustomTip",
                        "keyword_value" => "Add custom tip"
                    ],
                ]
            ],

            [
                "screenID" => "33",
                "ScreenName" => "Chat Screen",
                "keyword_data" => [
                    [
                        "screenId" => "33",
                        "keyword_id" => 432,
                        "keyword_name" => "Messages",
                        "keyword_value" => "Messages"
                    ],
                ]
            ],

            [
                "screenID" => "37",
                "ScreenName" => "Document Screen",
                "keyword_data" => [
                    [
                        "screenId" => "37",
                        "keyword_id" => 433,
                        "keyword_name" => "timeAreEstimated",
                        "keyword_value" => "Times are estimated based on predicated traffic. Actual traffic impact your drop-off time"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 434,
                        "keyword_name" => "chooseTime",
                        "keyword_value" => "Choose a time"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 435,
                        "keyword_name" => "pickUpAt",
                        "keyword_value" => "Pick-up at"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 436,
                        "keyword_name" => "selectDate",
                        "keyword_value" => "Select Date"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 437,
                        "keyword_name" => "selectTime",
                        "keyword_value" => "Select Time"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 438,
                        "keyword_name" => "selectProperLocation",
                        "keyword_value" => "Please select proper location"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 439,
                        "keyword_name" => "cancelRequest",
                        "keyword_value" => "Cancel Request"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 440,
                        "keyword_name" => "allScheduledRides",
                        "keyword_value" => "All Scheduled Rides"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 441,
                        "keyword_name" => "uploadDocument",
                        "keyword_value" => "Upload Document"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 442,
                        "keyword_name" => "uploadYourDocuments",
                        "keyword_value" => "Upload your Documents"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 443,
                        "keyword_name" => "carInfo",
                        "keyword_value" => "Car Info"
                    ],
                    [
                        "screenId" => "37",
                        "keyword_id" => 444,
                        "keyword_name" => "thisFieldIsRequired",
                        "keyword_value" => "This field is required"
                    ],
                ]
            ],

            [
                "screenID" => "40",
                "ScreenName" => "Loyalty Screen",
                "keyword_data" => [
                    [
                        "screenId" => "40",
                        "keyword_id" => 380,
                        "keyword_name" => "loyalty_points",
                        "keyword_value" => "Loyalty Points"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 381,
                        "keyword_name" => "points",
                        "keyword_value" => "Points"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 382,
                        "keyword_name" => "transfer_to_wallet",
                        "keyword_value" => "Transfer to wallet"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 383,
                        "keyword_name" => "your_total_points_is",
                        "keyword_value" => "Your Total points is"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 384,
                        "keyword_name" => "enter_points",
                        "keyword_value" => "Enter points"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 385,
                        "keyword_name" => "your_amount",
                        "keyword_value" => "Your amount"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 386,
                        "keyword_name" => "transfer",
                        "keyword_value" => "Transfer"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 387,
                        "keyword_name" => "point_history",
                        "keyword_value" => "Point History"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 388,
                        "keyword_name" => "earn_cash_with_loyalty_points",
                        "keyword_value" => "Earn Cash with Loyalty Points!"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 389,
                        "keyword_name" => "every_time_you_make_a_trip",
                        "keyword_value" => "Every time you make a trip, you earn loyalty points..."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 390,
                        "keyword_name" => "how_it_works",
                        "keyword_value" => "How it works:"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 391,
                        "keyword_name" => "earn_points_on_every_ride",
                        "keyword_value" => "Earn points on every Ride."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 392,
                        "keyword_name" => "redeem_points_for_discounts",
                        "keyword_value" => "Redeem points for discounts"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 393,
                        "keyword_name" => "track_your_points_easily_in_your_account_dashboard",
                        "keyword_value" => "Track your points easily in your account dashboard."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 394,
                        "keyword_name" => "you_have_withdrawn",
                        "keyword_value" => "You have withdrawn"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 395,
                        "keyword_name" => "transfer_id_is",
                        "keyword_value" => "Transfer ID is"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 396,
                        "keyword_name" => "you_have_earned",
                        "keyword_value" => "You have Earned."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 397,
                        "keyword_name" => "trip_completed_by_you_trip_id_is",
                        "keyword_value" => "Ride completed by you. Ride id is"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 398,
                        "keyword_name" => "pleaseEnterPoints",
                        "keyword_value" => "Please enter points"
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 399,
                        "keyword_name" => "your_points_must_be_greater_than_zero",
                        "keyword_value" => "Your points must be greater than zero."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 400,
                        "keyword_name" => "your_points_is_more_than_your_total_points",
                        "keyword_value" => "Your points is more than your total points."
                    ],
                    [
                        "screenId" => "40",
                        "keyword_id" => 448,
                        "keyword_name" => "youHaveBeenAwarded",
                        "keyword_value" => "You have been awarded"
                    ],
                    [
                        "screenId" => "34",
                        "keyword_id" => 452,
                        "keyword_name" => "estTime",
                        "keyword_value" => "Est. Time"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 458,
                        "keyword_name" => "rideEstimate",
                        "keyword_value" => "Ride Estimate"
                    ],
                    [
                        "screenId" => "25",
                        "keyword_id" => 457,
                        "keyword_name" => "driverArriveIn",
                        "keyword_value" => "Driver arriving in"
                    ],
                ]
            ],

        ];
        $languages = \App\Models\LanguageList::all();

        // INSERT SCREEN AND KEYWORDS
        foreach ($screen_data as $screen) {

            $screen_record = Screen::firstOrCreate(
                ['screenId' => $screen['screenID']],
                ['screenName' => $screen['ScreenName']]
            );

            if (!empty($screen['keyword_data'])) {
                foreach ($screen['keyword_data'] as $keyword) {

                    $keywordData =DefaultKeyword::firstOrCreate(
                        ['keyword_id' => $keyword['keyword_id']],
                        [
                            'screen_id' => $screen_record->screenId,
                            'keyword_name' => $keyword['keyword_name'],
                            'keyword_value' => $keyword['keyword_value'],
                        ]
                    );
                    foreach ($languages as $language) {
                        \App\Models\LanguageWithKeyword::firstOrCreate(
                            [
                                'language_id' => $language->id,
                                'keyword_id' => $keywordData->keyword_id,
                            ],
                            [
                                'screen_id' => $keywordData->screen_id,
                                'keyword_value' => $keywordData->keyword_value,
                            ]
                        );
                    }
                }
            }
        }
    }
}