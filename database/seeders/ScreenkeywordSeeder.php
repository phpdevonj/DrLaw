<?php

namespace Database\Seeders;

use App\Models\DefaultKeyword;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;
use Illuminate\Database\Seeder;
use App\Models\Screen;

class ScreenkeywordSeeder extends Seeder
{

  /**
   * Auto generated seed file
   *
   * @return void
   */
  public function run()
  {
    $languageListIds = LanguageList::pluck('id')->toArray();

    $fetchedKeywords = LanguageWithKeyword::whereIn('language_id', $languageListIds)
      ->pluck('keyword_id')
      ->toArray();

    $screen_data =
    [
      [
        "screenID" => "1",
        "ScreenName" => "WalkThroughScreen",
        "keyword_data" => [
          [
            "screenId" => "1",
            "keyword_id" => 362,
            "keyword_name" => "driver_walkthrough_title_1",
            "keyword_value" => "Get Ride Request"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 363,
            "keyword_name" => "driver_walkthrough_title_2",
            "keyword_value" => "Pickup Rider"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 364,
            "keyword_name" => "driver_walkthrough_title_3",
            "keyword_value" => "Drop Rider"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 365,
            "keyword_name" => "driver_walkthrough_subtitle_1",
            "keyword_value" => "Get A Ride Request By Nearest Rider"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 366,
            "keyword_name" => "driver_walkthrough_subtitle_2",
            "keyword_value" => "Accept A Ride Request And Pickup A Rider For Destination"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 367,
            "keyword_name" => "driver_walkthrough_subtitle_3",
            "keyword_value" => "Drop A Rider To Destination"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 270,
            "keyword_name" => "walkthrough_title_1",
            "keyword_value" => "Select your Ride"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 271,
            "keyword_name" => "walkthrough_title_2",
            "keyword_value" => "Navigating Your Ride"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 272,
            "keyword_name" => "walkthrough_title_3",
            "keyword_value" => "Track your Ride"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 273,
            "keyword_name" => "walkthrough_subtitle_1",
            "keyword_value" => "Request a ride get picked up by a nearby community driver"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 274,
            "keyword_name" => "walkthrough_subtitle_2",
            "keyword_value" => "Seamless Travel, Smart Choices Stress-Free Journeys"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 275,
            "keyword_name" => "walkthrough_subtitle_3",
            "keyword_value" => "Know your Service and be able to view current location in real time on the map"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 129,
            "keyword_name" => "male",
            "keyword_value" => "Male"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 130,
            "keyword_name" => "female",
            "keyword_value" => "Female"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 131,
            "keyword_name" => "other",
            "keyword_value" => "Other"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 143,
            "keyword_name" => "duration",
            "keyword_value" => "Duration"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 151,
            "keyword_name" => "complainList",
            "keyword_value" => "Complain List"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 166,
            "keyword_name" => "customerName",
            "keyword_value" => "Customer Name"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 170,
            "keyword_name" => "orderedDate",
            "keyword_value" => "Ordered Date"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 204,
            "keyword_name" => "unsupportedPlateForm",
            "keyword_value" => "Unsupported Platform"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 205,
            "keyword_name" => "invoiceCapital",
            "keyword_value" => "INVOICE"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 206,
            "keyword_name" => "description",
            "keyword_value" => "Description"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 207,
            "keyword_name" => "price",
            "keyword_value" => "Price"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 218,
            "keyword_name" => "locationNotAvailable",
            "keyword_value" => "Location Not Available"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 226,
            "keyword_name" => "skip",
            "keyword_value" => "Skip"
          ],
          [
            "screenId" => "1",
            "keyword_id" => 257,
            "keyword_name" => "rideComplete",
            "keyword_value" => "Confirm Ride Complete"
          ]
        ]
      ],
      [
        "screenID" => "2",
        "ScreenName" => "Register Screen",
        "keyword_data" => [
          [
            "screenId" => "2",
            "keyword_id" => 9,
            "keyword_name" => "signUp",
            "keyword_value" => "Sign Up"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 10,
            "keyword_name" => "createAccount",
            "keyword_value" => "Create Account"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 11,
            "keyword_name" => "firstName",
            "keyword_value" => "First Name"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 12,
            "keyword_name" => "lastName",
            "keyword_value" => "Last Name"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 13,
            "keyword_name" => "userName",
            "keyword_value" => "Username"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 14,
            "keyword_name" => "phoneNumber",
            "keyword_value" => "Phone Number"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 15,
            "keyword_name" => "alreadyHaveAnAccount",
            "keyword_value" => "Already have an account?"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 16,
            "keyword_name" => "changePassword",
            "keyword_value" => "Change Password"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 17,
            "keyword_name" => "oldPassword",
            "keyword_value" => "Old Password"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 18,
            "keyword_name" => "newPassword",
            "keyword_value" => "New Password"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 19,
            "keyword_name" => "confirmPassword",
            "keyword_value" => "Confirm Password"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 20,
            "keyword_name" => "passwordDoesNotMatch",
            "keyword_value" => "Passwords do not match"
          ],
          [
            "screenId" => "2",
            "keyword_id" => 21,
            "keyword_name" => "passwordInvalid",
            "keyword_value" => "The minimum required password length is 8 characters."
          ],
          [
            "screenId" => "2",
            "keyword_id" => 157,
            "keyword_name" => "pleaseAcceptTermsOfServicePrivacyPolicy",
            "keyword_value" => "Please accept the Terms of Service and Privacy Policy"
          ]
        ]
      ],
      [
        "screenID" => "3",
        "ScreenName" => "Login Screen",
        "keyword_data" => [
          [
            "screenId" => "3",
            "keyword_id" => 2,
            "keyword_name" => "thisFieldRequired",
            "keyword_value" => "This field is required"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 3,
            "keyword_name" => "email",
            "keyword_value" => "Email"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 4,
            "keyword_name" => "password",
            "keyword_value" => "Password"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 5,
            "keyword_name" => "forgotPassword",
            "keyword_value" => "Forgot Password?"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 6,
            "keyword_name" => "logIn",
            "keyword_value" => "Log In"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 7,
            "keyword_name" => "orLogInWith",
            "keyword_value" => "Or log in with"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 8,
            "keyword_name" => "donHaveAnAccount",
            "keyword_value" => "Don’t have an account?"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 116,
            "keyword_name" => "signInUsingYourMobileNumber",
            "keyword_value" => "Sign in using your mobile number"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 117,
            "keyword_name" => "otp",
            "keyword_value" => "OTP"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 158,
            "keyword_name" => "rememberMe",
            "keyword_value" => "Remember Me"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 159,
            "keyword_name" => "iAgreeToThe",
            "keyword_value" => "I agree to the"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 228,
            "keyword_name" => "validateOtp",
            "keyword_value" => "Validate OTP"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 229,
            "keyword_name" => "otpCodeHasBeenSentTo",
            "keyword_value" => "OTP code has been sent to"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 230,
            "keyword_name" => "pleaseEnterOtp",
            "keyword_value" => "Please enter OTP below to verify your mobile number."
          ],
          [
            "screenId" => "3",
            "keyword_id" => 240,
            "keyword_name" => "welcome",
            "keyword_value" => "Welcome"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 241,
            "keyword_name" => "signContinue",
            "keyword_value" => "Sign In to Continue"
          ],
          [
            "screenId" => "3",
            "keyword_id" => 242,
            "keyword_name" => "passwordLength",
            "keyword_value" => "Password length at least should be 8"
          ]
        ]
      ],
      [
        "screenID" => "4",
        "ScreenName" => "Forget Password Screen",
        "keyword_data" => [
          [
            "screenId" => "4",
            "keyword_id" => 25,
            "keyword_name" => "enterTheEmailAssociatedWithYourAccount",
            "keyword_value" => "Enter the email associated with your account"
          ],
          [
            "screenId" => "4",
            "keyword_id" => 26,
            "keyword_name" => "submit",
            "keyword_value" => "Submit"
          ]
        ]
      ],
      [
        "screenID" => "5",
        "ScreenName" => "Dashboard Screen",
        "keyword_data" => [
          [
            "screenId" => "5",
            "keyword_id" => 92,
            "keyword_name" => "logOut",
            "keyword_value" => "Log Out"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 93,
            "keyword_name" => "areYouSureYouWantToLogoutThisApp",
            "keyword_value" => "Are you certain you wish to log out of this application?"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 94,
            "keyword_name" => "whatWouldYouLikeToGo",
            "keyword_value" => "where you'd like to go?"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 95,
            "keyword_name" => "enterYourDestination",
            "keyword_value" => "Enter your destination"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 96,
            "keyword_name" => "currentLocation",
            "keyword_value" => "Current Location"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 97,
            "keyword_name" => "destinationLocation",
            "keyword_value" => "Destination Location"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 98,
            "keyword_name" => "chooseOnMap",
            "keyword_value" => "Choose on Map"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 99,
            "keyword_name" => "profile",
            "keyword_value" => "Profile"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 104,
            "keyword_name" => "lookingForNearbyDrivers",
            "keyword_value" => "Looking for nearby drivers"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 105,
            "keyword_name" => "weAreLookingForNearDriversAcceptsYourRide",
            "keyword_value" => "We are looking for nearby drivers to accept your ride"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 167,
            "keyword_name" => "sourceLocation",
            "keyword_value" => "Source Location"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 192,
            "keyword_name" => "cancelledReason",
            "keyword_value" => "Cancelled Reason"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 232,
            "keyword_name" => "whoWillBeSeated",
            "keyword_value" => "Who will be seated?"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 233,
            "keyword_name" => "via",
            "keyword_value" => "Via"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 234,
            "keyword_name" => "status",
            "keyword_value" => "Status"
          ],
          [
            "screenId" => "5",
            "keyword_id" => 267,
            "keyword_name" => "addDropPoint",
            "keyword_value" => "+Add drop point"
          ]
        ]
      ],
      [
        "screenID" => "6",
        "ScreenName" => "Notifications Screen",
        "keyword_data" => [
          [
            "screenId" => "6",
            "keyword_id" => 28,
            "keyword_name" => "notification",
            "keyword_value" => "Notifications"
          ]
        ]
      ],
      [
        "screenID" => "7",
        "ScreenName" => "Profile Screen",
        "keyword_data" => [
          [
            "screenId" => "7",
            "keyword_id" => 36,
            "keyword_name" => "editProfile",
            "keyword_value" => "Edit Profile"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 37,
            "keyword_name" => "address",
            "keyword_value" => "Address"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 38,
            "keyword_name" => "updateProfile",
            "keyword_value" => "Update Profile"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 39,
            "keyword_name" => "notChangeUsername",
            "keyword_value" => "You cannot change username"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 40,
            "keyword_name" => "notChangeEmail",
            "keyword_value" => "You cannot Change email id"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 41,
            "keyword_name" => "profileUpdateMsg",
            "keyword_value" => "Profile updated successfully"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 150,
            "keyword_name" => "youCannotChangePhoneNumber",
            "keyword_value" => "You cannot change your phone number"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 208,
            "keyword_name" => "gallery",
            "keyword_value" => "Gallery"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 209,
            "keyword_name" => "camera",
            "keyword_value" => "Camera"
          ],
          [
            "screenId" => "7",
            "keyword_id" => 231,
            "keyword_name" => "selectSources",
            "keyword_value" => "Select Sources"
          ]
        ]
      ],
      [
        "screenID" => "9",
        "ScreenName" => "Ride Details Screen",
        "keyword_data" => [
          [
            "screenId" => "9",
            "keyword_id" => 344,
            "keyword_name" => "minimumFees",
            "keyword_value" => "Minimum fees"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 345,
            "keyword_name" => "tips",
            "keyword_value" => "Tips"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 320,
            "keyword_name" => "riderInformation",
            "keyword_value" => "RIDER INFORMATION"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 312,
            "keyword_name" => "aboutRider",
            "keyword_value" => "About Rider"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 308,
            "keyword_name" => "cashCollected",
            "keyword_value" => "Cash Collected"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 309,
            "keyword_name" => "areYouSureCollectThisPayment",
            "keyword_value" => "Could you please confirm if you are collecting this payment?"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 89,
            "keyword_name" => "aboutDriver",
            "keyword_value" => "About Driver"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 90,
            "keyword_name" => "rideHistory",
            "keyword_value" => "Ride History"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 160,
            "keyword_name" => "driverInformation",
            "keyword_value" => "Driver Information"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 172,
            "keyword_name" => "lblRide",
            "keyword_value" => "Ride"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 173,
            "keyword_name" => "lblRideInformation",
            "keyword_value" => "Ride Information"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 235,
            "keyword_name" => "riderInformation",
            "keyword_value" => "Rider Information"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 236,
            "keyword_name" => "minutePrice",
            "keyword_value" => "Minute Price"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 237,
            "keyword_name" => "waitingTimePrice",
            "keyword_value" => "Waiting Time Price"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 238,
            "keyword_name" => "additionalFees",
            "keyword_value" => "Additional Fees"
          ],
          [
            "screenId" => "9",
            "keyword_id" => 258,
            "keyword_name" => "detailScreen",
            "keyword_value" => "Ride Details"
          ]
        ]
      ],
      [
        "screenID" => "11",
        "ScreenName" => "Complaint Screen",
        "keyword_data" => [
          [
            "screenId" => "11",
            "keyword_id" => 342,
            "keyword_name" => "chatWithAdmin",
            "keyword_value" => "Chat with Admin"
          ],
          [
            "screenId" => "11",
            "keyword_id" => 32,
            "keyword_name" => "complain",
            "keyword_value" => "Complain"
          ],
          [
            "screenId" => "11",
            "keyword_id" => 33,
            "keyword_name" => "pleaseEnterSubject",
            "keyword_value" => "Please enter a subject"
          ],
          [
            "screenId" => "11",
            "keyword_id" => 34,
            "keyword_name" => "writeDescription",
            "keyword_value" => "Write a description...."
          ],
          [
            "screenId" => "11",
            "keyword_id" => 35,
            "keyword_name" => "saveComplain",
            "keyword_value" => "Save Complain"
          ]
        ]
      ],
      [
        "screenID" => "12",
        "ScreenName" => "Complaint Contact Screen",
        "keyword_data" => [
          [
            "screenId" => "12",
            "keyword_id" => 152,
            "keyword_name" => "writeMsg",
            "keyword_value" => "Write Message"
          ],
          [
            "screenId" => "12",
            "keyword_id" => 153,
            "keyword_name" => "pleaseEnterMsg",
            "keyword_value" => "Please enter a message"
          ],
          [
            "screenId" => "12",
            "keyword_id" => 154,
            "keyword_name" => "viewAll",
            "keyword_value" => "View All"
          ]
        ]
      ],
      [
        "screenID" => "13",
        "ScreenName" => "Payment Screen",
        "keyword_data" => [
          [
            "screenId" => "13",
            "keyword_id" => 201,
            "keyword_name" => "perMinDrive",
            "keyword_value" => "Per Minute Drive"
          ],
          [
            "screenId" => "13",
            "keyword_id" => 202,
            "keyword_name" => "perMinWait",
            "keyword_value" => "Per Minute Wait"
          ],
          [
            "screenId" => "13",
            "keyword_id" => 220,
            "keyword_name" => "paymentFailed",
            "keyword_value" => "Payment Failed"
          ],
          [
            "screenId" => "13",
            "keyword_id" => 221,
            "keyword_name" => "checkConsoleForError",
            "keyword_value" => "Check console for error"
          ],
          [
            "screenId" => "13",
            "keyword_id" => 222,
            "keyword_name" => "transactionFailed",
            "keyword_value" => "The transaction was unsuccessful."
          ],
          [
            "screenId" => "13",
            "keyword_id" => 223,
            "keyword_name" => "transactionSuccessful",
            "keyword_value" => "Your transaction has been successfully."
          ],
          [
            "screenId" => "13",
            "keyword_id" => 224,
            "keyword_name" => "payWithCard",
            "keyword_value" => "Pay with Card"
          ],
          [
            "screenId" => "13",
            "keyword_id" => 225,
            "keyword_name" => "success",
            "keyword_value" => "Success"
          ]
        ]
      ],
      [
        "screenID" => "14",
        "ScreenName" => "Wallet Screen",
        "keyword_data" => [
          [
            "screenId" => "14",
            "keyword_id" => 46,
            "keyword_name" => "availableBalance",
            "keyword_value" => "Available Balance"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 47,
            "keyword_name" => "recentTransactions",
            "keyword_value" => "Recent Transactions"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 48,
            "keyword_name" => "moneyDeposited",
            "keyword_value" => "Money Deposited"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 49,
            "keyword_name" => "addMoney",
            "keyword_value" => "Add Money"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 51,
            "keyword_name" => "pleaseSelectAmount",
            "keyword_value" => "Please select an amount"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 52,
            "keyword_name" => "amount",
            "keyword_value" => "Amount"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 156,
            "keyword_name" => "moneyDebit",
            "keyword_value" => "Money Debit"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 183,
            "keyword_name" => "maximum",
            "keyword_value" => "Maximum"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 184,
            "keyword_name" => "required",
            "keyword_value" => "Required"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 185,
            "keyword_name" => "minimum",
            "keyword_value" => "Minimum"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 186,
            "keyword_name" => "withDraw",
            "keyword_value" => "Withdraw"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 187,
            "keyword_name" => "withdrawHistory",
            "keyword_value" => "Withdraw History"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 188,
            "keyword_name" => "approved",
            "keyword_value" => "Approved"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 244,
            "keyword_name" => "missingBankDetail",
            "keyword_value" => "Missing Bank Details. Complete Your Profile to withdrawal amount"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 245,
            "keyword_name" => "close",
            "keyword_value" => "Close"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 259,
            "keyword_name" => "bankInfoNotFound",
            "keyword_value" => "Bank Info Not Found"
          ],
          [
            "screenId" => "14",
            "keyword_id" => 260,
            "keyword_name" => "noBalanceValidate",
            "keyword_value" => "Unable to proceed Insufficient wallet balance."
          ]
        ]
      ],
      [
        "screenID" => "15",
        "ScreenName" => "Bank Details Screen",
        "keyword_data" => [
          [
            "screenId" => "15",
            "keyword_id" => 210,
            "keyword_name" => "bankInfoUpdated",
            "keyword_value" => "Your bank information has been successfully updated."
          ],
          [
            "screenId" => "15",
            "keyword_id" => 211,
            "keyword_name" => "bankInfo",
            "keyword_value" => "Bank Info"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 212,
            "keyword_name" => "bankName",
            "keyword_value" => "Bank Name"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 213,
            "keyword_name" => "bankCode",
            "keyword_value" => "Bank Code"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 214,
            "keyword_name" => "accountHolderName",
            "keyword_value" => "Account Holder Name"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 215,
            "keyword_name" => "accountNumber",
            "keyword_value" => "Account Number"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 216,
            "keyword_name" => "updateBankDetail",
            "keyword_value" => "Update Bank Detail"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 217,
            "keyword_name" => "addBankDetail",
            "keyword_value" => "Add Bank Detail"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 261,
            "keyword_name" => "iban",
            "keyword_value" => "Bank IBAN"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 262,
            "keyword_name" => "swift",
            "keyword_value" => "Bank Swift"
          ],
          [
            "screenId" => "15",
            "keyword_id" => 263,
            "keyword_name" => "routingNumber",
            "keyword_value" => "Routing Number"
          ]
        ]
      ],
      [
        "screenID" => "16",
        "ScreenName" => "Withdraw Screen",
        "keyword_data" => [
          [
            "screenId" => "16",
            "keyword_id" => 189,
            "keyword_name" => "requested",
            "keyword_value" => "Requested"
          ],
          [
            "screenId" => "16",
            "keyword_id" => 227,
            "keyword_name" => "declined",
            "keyword_value" => "Declined"
          ]
        ]
      ],
      [
        "screenID" => "17",
        "ScreenName" => "Emergency Contacts Screen",
        "keyword_data" => [
          [
            "screenId" => "17",
            "keyword_id" => 42,
            "keyword_name" => "emergencyContact",
            "keyword_value" => "Emergency Contact"
          ],
          [
            "screenId" => "17",
            "keyword_id" => 44,
            "keyword_name" => "addContact",
            "keyword_value" => "Add Contact"
          ],
          [
            "screenId" => "17",
            "keyword_id" => 45,
            "keyword_name" => "save",
            "keyword_value" => "Save"
          ],
          [
            "screenId" => "17",
            "keyword_id" => 91,
            "keyword_name" => "emergencyContacts",
            "keyword_value" => "Emergency Contacts"
          ]
        ]
      ],
      [
        "screenID" => "18",
        "ScreenName" => "Settings Screen",
        "keyword_data" => [
          [
            "screenId" => "18",
            "keyword_id" => 27,
            "keyword_name" => "language",
            "keyword_value" => "Languages"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 100,
            "keyword_name" => "privacyPolicy",
            "keyword_value" => "Privacy Policy"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 101,
            "keyword_name" => "helpSupport",
            "keyword_value" => "Help & Support"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 102,
            "keyword_name" => "termsConditions",
            "keyword_value" => "Terms & Conditions"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 103,
            "keyword_name" => "aboutUs",
            "keyword_value" => "About Us"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 141,
            "keyword_name" => "txtURLEmpty",
            "keyword_value" => "URL is empty"
          ],
          [
            "screenId" => "18",
            "keyword_id" => 239,
            "keyword_name" => "settings",
            "keyword_value" => "Settings"
          ]
        ]
      ],
      [
        "screenID" => "19",
        "ScreenName" => "Change Password Screen",
        "keyword_data" => [
          [
            "screenId" => "19",
            "keyword_id" => 243,
            "keyword_name" => "bothPasswordNotMatch",
            "keyword_value" => "Both password should be matched"
          ]
        ]
      ],
      [
        "screenID" => "23",
        "ScreenName" => "About us Screen",
        "keyword_data" => [
          [
            "screenId" => "23",
            "keyword_id" => 142,
            "keyword_name" => "lblFollowUs",
            "keyword_value" => "Follow Us"
          ]
        ]
      ],
      [
        "screenID" => "24",
        "ScreenName" => "Delete Account Screen",
        "keyword_data" => [
          [
            "screenId" => "24",
            "keyword_id" => 22,
            "keyword_name" => "yes",
            "keyword_value" => "Yes"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 23,
            "keyword_name" => "no",
            "keyword_value" => "No"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 43,
            "keyword_name" => "areYouSureYouWantDeleteThisNumber",
            "keyword_value" => "Are you certain you wish to delete this number?"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 132,
            "keyword_name" => "deleteAccount",
            "keyword_value" => "Delete Account"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 133,
            "keyword_name" => "account",
            "keyword_value" => "Account"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 134,
            "keyword_name" => "areYouSureYouWantPleaseReadAffect",
            "keyword_value" => "Are you sure you want to delete your account? Please read how account deletion will affect."
          ],
          [
            "screenId" => "24",
            "keyword_id" => 135,
            "keyword_name" => "deletingAccountEmail",
            "keyword_value" => "Deleting your account removes personal information from our database. Your email becomes permanently reserved and same email cannot be re-used to register a new account"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 136,
            "keyword_name" => "areYouSureYouWantDeleteAccount",
            "keyword_value" => "Are you sure you want to delete Account?"
          ],
          [
            "screenId" => "24",
            "keyword_id" => 145,
            "keyword_name" => "demoMsg",
            "keyword_value" => "Tester role not allowed to perform this action"
          ]
        ]
      ],
      [
        "screenID" => "25",
        "ScreenName" => "Ride Status Screen",
        "keyword_data" => [
          [
            "screenId" => "25",
            "keyword_id" => 29,
            "keyword_name" => "useInCaseOfEmergency",
            "keyword_value" => "USE IN CASE OF EMERGENCY"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 30,
            "keyword_name" => "notifyAdmin",
            "keyword_value" => "Notify Admin"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 31,
            "keyword_name" => "notifiedSuccessfully",
            "keyword_value" => "Notified successfully"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 50,
            "keyword_name" => "cancel",
            "keyword_value" => "Cancel"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 53,
            "keyword_name" => "capacity",
            "keyword_value" => "Capacity"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 54,
            "keyword_name" => "paymentMethod",
            "keyword_value" => "Payment Method"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 55,
            "keyword_name" => "chooseYouPaymentLate",
            "keyword_value" => "Choose you payment now or late"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 56,
            "keyword_name" => "enterPromoCode",
            "keyword_value" => "Enter promo code"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 57,
            "keyword_name" => "confirm",
            "keyword_value" => "Confirm"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 58,
            "keyword_name" => "forInstantPayment",
            "keyword_value" => "For instant payment"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 59,
            "keyword_name" => "bookNow",
            "keyword_value" => "Book Now"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 60,
            "keyword_name" => "wallet",
            "keyword_value" => "Wallet"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 88,
            "keyword_name" => "continueD",
            "keyword_value" => "Continue"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 106,
            "keyword_name" => "get",
            "keyword_value" => "Get"
          ],
          [
            "screenId" => "8",
            "keyword_id" => 107,
            "keyword_name" => "rides",
            "keyword_value" => "Rides"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 108,
            "keyword_name" => "people",
            "keyword_value" => "People"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 109,
            "keyword_name" => "done",
            "keyword_value" => "Done"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 110,
            "keyword_name" => "availableOffers",
            "keyword_value" => "Available Offers"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 111,
            "keyword_name" => "off",
            "keyword_value" => "OFF"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 112,
            "keyword_name" => "sendOTP",
            "keyword_value" => "Send OTP"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 113,
            "keyword_name" => "carModel",
            "keyword_value" => "Car Model"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 114,
            "keyword_name" => "sos",
            "keyword_value" => "SOS"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 118,
            "keyword_name" => "newRideRequested",
            "keyword_value" => "New Ride Requested"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 119,
            "keyword_name" => "accepted",
            "keyword_value" => "Accepted"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 120,
            "keyword_name" => "arriving",
            "keyword_value" => "Arriving"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 121,
            "keyword_name" => "arrived",
            "keyword_value" => "Arrived"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 122,
            "keyword_name" => "inProgress",
            "keyword_value" => "In Progress"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 123,
            "keyword_name" => "cancelled",
            "keyword_value" => "Cancelled"
          ],
          [
            "screenId" => "8",
            "keyword_id" => 124,
            "keyword_name" => "completed",
            "keyword_value" => "Completed"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 126,
            "keyword_name" => "pending",
            "keyword_value" => "Pending"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 127,
            "keyword_name" => "failed",
            "keyword_value" => "Failed"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 128,
            "keyword_name" => "paid",
            "keyword_value" => "Paid"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 144,
            "keyword_name" => "paymentVia",
            "keyword_value" => "Payment Via"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 161,
            "keyword_name" => "nameFieldIsRequired",
            "keyword_value" => "Name field is required"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 162,
            "keyword_name" => "phoneNumberIsRequired",
            "keyword_value" => "Phone number is required"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 163,
            "keyword_name" => "enterName",
            "keyword_value" => "Enter Name"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 164,
            "keyword_name" => "enterContactNumber",
            "keyword_value" => "Enter Contact Number"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 171,
            "keyword_name" => "lblCarNumberPlate",
            "keyword_value" => "Car Number Plate"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 174,
            "keyword_name" => "lblWhereAreYou",
            "keyword_value" => "Where are you?"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 175,
            "keyword_name" => "lblDropOff",
            "keyword_value" => "Drop Off"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 176,
            "keyword_name" => "lblDistance",
            "keyword_value" => "Distance"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 177,
            "keyword_name" => "lblSomeoneElse",
            "keyword_value" => "Someone Else"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 178,
            "keyword_name" => "lblYou",
            "keyword_value" => "You"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 179,
            "keyword_name" => "lblWhoRidingMsg",
            "keyword_value" => "Confirm the rider & make sure the trip information"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 180,
            "keyword_name" => "lblNext",
            "keyword_value" => "Next"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 181,
            "keyword_name" => "lblLessWalletAmount",
            "keyword_value" => "Note : You have an insufficient balance in your wallet. Add the amount otherwise, you have to pay via cash."
          ],
          [
            "screenId" => "25",
            "keyword_id" => 182,
            "keyword_name" => "lblPayWhenEnds",
            "keyword_value" => "Pay when trip ends"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 190,
            "keyword_name" => "minimumFare",
            "keyword_value" => "Minimum Fare"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 191,
            "keyword_name" => "cancelRide",
            "keyword_value" => "Cancel Ride"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 193,
            "keyword_name" => "selectReason",
            "keyword_value" => "Select Reason"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 194,
            "keyword_name" => "writeReasonHere",
            "keyword_value" => "Write reason here..."
          ],
          [
            "screenId" => "25",
            "keyword_id" => 195,
            "keyword_name" => "driverGoingWrongDirection",
            "keyword_value" => "Driver going in the wrong direction"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 196,
            "keyword_name" => "pickUpTimeTakingTooLong",
            "keyword_value" => "Pickup time is taking too long"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 197,
            "keyword_name" => "driverAskedMeToCancel",
            "keyword_value" => "Driver asked me to cancel"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 198,
            "keyword_name" => "others",
            "keyword_value" => "Others"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 199,
            "keyword_name" => "baseFare",
            "keyword_value" => "Base Fare"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 200,
            "keyword_name" => "perDistance",
            "keyword_value" => "Per Distance"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 203,
            "keyword_name" => "min",
            "keyword_value" => "Min"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 219,
            "keyword_name" => "servicesNotFound",
            "keyword_value" => "Services Not Found"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 246,
            "keyword_name" => "copied",
            "keyword_value" => "Copied"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 247,
            "keyword_name" => "noNearByDriverFound",
            "keyword_value" => "Oops No Near by Drivers Found!"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 249,
            "keyword_name" => "safetyConcerns",
            "keyword_value" => "Safety Concerns"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 250,
            "keyword_name" => "driverNotShown",
            "keyword_value" => "Driver didn't show up"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 251,
            "keyword_name" => "noNeedRide",
            "keyword_value" => "Don't need a ride anymore"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 252,
            "keyword_name" => "infoNotMatch",
            "keyword_value" => "Driver/vehicle info didn't match"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 253,
            "keyword_name" => "rideCanceledByDriver",
            "keyword_value" => "Ride canceled by Driver"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 264,
            "keyword_name" => "fixedPrice",
            "keyword_value" => "Fixed Charge"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 265,
            "keyword_name" => "viewDropLocations",
            "keyword_value" => "View Drop Locations"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 266,
            "keyword_name" => "viewMore",
            "keyword_value" => "View More"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 268,
            "keyword_name" => "dropPoint",
            "keyword_value" => "Drop Point"
          ],
          [
            "screenId" => "25",
            "keyword_id" => 269,
            "keyword_name" => "needToEditRide",
            "keyword_value" => "Need to edit my details"
          ]
        ]
      ],
      [
        "screenID" => "26",
        "ScreenName" => "Ride Payment Screen",
        "keyword_data" => [
          [
            "screenId" => "26",
            "keyword_id" => 61,
            "keyword_name" => "paymentDetail",
            "keyword_value" => "Payment Detail"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 62,
            "keyword_name" => "rideId",
            "keyword_value" => "Ride ID"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 63,
            "keyword_name" => "viewHistory",
            "keyword_value" => "View History"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 64,
            "keyword_name" => "paymentDetails",
            "keyword_value" => "Payment Details"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 65,
            "keyword_name" => "paymentType",
            "keyword_value" => "Payment Type"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 66,
            "keyword_name" => "paymentStatus",
            "keyword_value" => "Payment Status"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 67,
            "keyword_name" => "priceDetail",
            "keyword_value" => "Price Detail"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 68,
            "keyword_name" => "basePrice",
            "keyword_value" => "Base Price"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 69,
            "keyword_name" => "distancePrice",
            "keyword_value" => "Distance Price"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 70,
            "keyword_name" => "waitTime",
            "keyword_value" => "Wait Time"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 71,
            "keyword_name" => "extraCharges",
            "keyword_value" => "Extra Charges"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 72,
            "keyword_name" => "couponDiscount",
            "keyword_value" => "Coupon Discount"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 73,
            "keyword_name" => "total",
            "keyword_value" => "Total"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 74,
            "keyword_name" => "payment",
            "keyword_value" => "Payment"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 75,
            "keyword_name" => "cash",
            "keyword_value" => "Cash"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 76,
            "keyword_name" => "updatePaymentStatus",
            "keyword_value" => "Update Payment Status"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 77,
            "keyword_name" => "waitingForDriverConformation",
            "keyword_value" => "Waiting for driver confirmation"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 78,
            "keyword_name" => "continueNewRide",
            "keyword_value" => "Continue with new ride"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 79,
            "keyword_name" => "payToPayment",
            "keyword_value" => "Pay to payment"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 80,
            "keyword_name" => "tip",
            "keyword_value" => "Tips"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 81,
            "keyword_name" => "pay",
            "keyword_value" => "Pay"
          ],
          [
            "screenId" => "26",
            "keyword_id" => 248,
            "keyword_name" => "paymentSuccess",
            "keyword_value" => "Payment Success"
          ]
        ]
      ],
      [
        "screenID" => "27",
        "ScreenName" => "Review Screen",
        "keyword_data" => [
          [
            "screenId" => "27",
            "keyword_id" => 340,
            "keyword_name" => "riderReview",
            "keyword_value" => "Rider Review"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 82,
            "keyword_name" => "howWasYourRide",
            "keyword_value" => "May I inquire about your experience during the ride?"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 83,
            "keyword_name" => "wouldYouLikeToAddTip",
            "keyword_value" => "Would you like to add a tip?"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 84,
            "keyword_name" => "addMoreTip",
            "keyword_value" => "Add more tip"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 85,
            "keyword_name" => "addMore",
            "keyword_value" => "Add more"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 86,
            "keyword_name" => "addReviews",
            "keyword_value" => "Add Review"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 87,
            "keyword_name" => "writeYourComments",
            "keyword_value" => "Write your reviews...."
          ],
          [
            "screenId" => "27",
            "keyword_id" => 115,
            "keyword_name" => "driverReview",
            "keyword_value" => "Driver Review"
          ],
          [
            "screenId" => "27",
            "keyword_id" => 155,
            "keyword_name" => "pleaseSelectRating",
            "keyword_value" => "Please select a rating"
          ]
        ]
      ],
      [
        "screenID" => "28",
        "ScreenName" => "Map Screen",
        "keyword_data" => [
          [
            "screenId" => "28",
            "keyword_id" => 146,
            "keyword_name" => "findPlace",
            "keyword_value" => "Find a place..."
          ],
          [
            "screenId" => "28",
            "keyword_id" => 147,
            "keyword_name" => "pleaseWait",
            "keyword_value" => "Please wait"
          ],
          [
            "screenId" => "28",
            "keyword_id" => 148,
            "keyword_name" => "selectPlace",
            "keyword_value" => "Select a Place"
          ],
          [
            "screenId" => "28",
            "keyword_id" => 149,
            "keyword_name" => "placeNotInArea",
            "keyword_value" => "Place not in area"
          ]
        ]
      ],
      [
        "screenID" => "29",
        "ScreenName" => "Invoice Screen",
        "keyword_data" => [
          [
            "screenId" => "29",
            "keyword_id" => 165,
            "keyword_name" => "invoice",
            "keyword_value" => "Invoice"
          ],
          [
            "screenId" => "29",
            "keyword_id" => 168,
            "keyword_name" => "invoiceNo",
            "keyword_value" => "Invoice No"
          ],
          [
            "screenId" => "29",
            "keyword_id" => 169,
            "keyword_name" => "invoiceDate",
            "keyword_value" => "Invoice Date"
          ]
        ]
      ],
      [
        "screenID" => "30",
        "ScreenName" => "Location Required Screen",
        "keyword_data" => [
          [
            "screenId" => "30",
            "keyword_id" => 307,
            "keyword_name" => "mostReliableMightyDriverApp",
            "keyword_value" => "Most Reliable Mighty Driver App"
          ],
          [
            "screenId" => "30",
            "keyword_id" => 125,
            "keyword_name" => "pleaseEnableLocationPermission",
            "keyword_value" => "Please enable location permission"
          ],
          [
            "screenId" => "30",
            "keyword_id" => 138,
            "keyword_name" => "allow",
            "keyword_value" => "Allow"
          ],
          [
            "screenId" => "30",
            "keyword_id" => 139,
            "keyword_name" => "mostReliableMightyRiderApp",
            "keyword_value" => "Most Reliable Mighty Rider App"
          ],
          [
            "screenId" => "30",
            "keyword_id" => 140,
            "keyword_name" => "toEnjoyYourRideExperiencePleaseAllowPermissions",
            "keyword_value" => "To enjoy your ride experience Please allow us the following permissions"
          ]
        ]
      ],
      [
        "screenID" => "31",
        "ScreenName" => "Network Required Screen",
        "keyword_data" => [
          [
            "screenId" => "31",
            "keyword_id" => 137,
            "keyword_name" => "yourInternetIsNotWorking",
            "keyword_value" => "I apologize, but it seems that your internet connection is currently not functioning."
          ],
          [
            "screenId" => "31",
            "keyword_id" => 254,
            "keyword_name" => "networkErr",
            "keyword_value" => "Network Error"
          ],
          [
            "screenId" => "31",
            "keyword_id" => 255,
            "keyword_name" => "tryAgain",
            "keyword_value" => "Try Again"
          ],
          [
            "screenId" => "31",
            "keyword_id" => 256,
            "keyword_name" => "noConnected",
            "keyword_value" => "Check your network connection"
          ]
        ]
      ],
      [
        "screenID" => "32",
        "ScreenName" => "Splash Screen",
        "keyword_data" => [
          [
            "screenId" => "32",
            "keyword_id" => 331,
            "keyword_name" => "pleaseContactSystemAdministrator",
            "keyword_value" => "Please contact system administrator"
          ],
          [
            "screenId" => "32",
            "keyword_id" => 330,
            "keyword_name" => "yourAccountIs",
            "keyword_value" => "Taxi Rider"
          ],
          [
            "screenId" => "32",
            "keyword_id" => 1,
            "keyword_name" => "appName",
            "keyword_value" => "Taxi Rider"
          ]
        ]
      ],
      [
        "screenID" => "33",
        "ScreenName" => "Chat Screen",
        "keyword_data" => [
          [
            "screenId" => "33",
            "keyword_id" => 24,
            "keyword_name" => "writeMessage",
            "keyword_value" => "Write Message"
          ]
        ]
      ],
      [
        "screenID" => "34",
        "ScreenName" => "Driver Dashboard Screen",
        "keyword_data" => [
          [
            "screenId" => "34",
            "keyword_id" => 346,
            "keyword_name" => "updateDrop",
            "keyword_value" => "Next stop"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 347,
            "keyword_name" => "finishMsg",
            "keyword_value" => "Could you please confirm if the ride has concluded?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 348,
            "keyword_name" => "extraFees",
            "keyword_value" => "Apply Extra Fees"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 349,
            "keyword_name" => "startRideAskOTP",
            "keyword_value" => "To start the ride, ask the client for their OTP."
          ],
          [
            "screenId" => "34",
            "keyword_id" => 350,
            "keyword_name" => "lessWalletAmountMsg",
            "keyword_value" => "You can't ride because your wallet balance is below the limit.  Add money to your wallet now to continue using the driver app and receive notifications"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 351,
            "keyword_name" => "chooseMap",
            "keyword_value" => "Choose Map"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 352,
            "keyword_name" => "ridingPerson",
            "keyword_value" => "Riding Person"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 353,
            "keyword_name" => "riderNotAnswer",
            "keyword_value" => "Rider Not answering"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 354,
            "keyword_name" => "accidentAccept",
            "keyword_value" => "Accidentally accepted the trip"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 355,
            "keyword_name" => "riderNotOnTime",
            "keyword_value" => "Rider didn't show up on time"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 356,
            "keyword_name" => "vehicleProblem",
            "keyword_value" => "Vehicle problems"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 357,
            "keyword_name" => "paymentSuccess",
            "keyword_value" => "Payment Successful"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 358,
            "keyword_name" => "estAmount",
            "keyword_value" => "Est. Amount"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 359,
            "keyword_name" => "dontFeelSafe",
            "keyword_value" => "I don't feel safe"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 360,
            "keyword_name" => "wrongTurn",
            "keyword_value" => "Made a wrong turn"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 361,
            "keyword_name" => "rideCanceledByRider",
            "keyword_value" => "Ride canceled by Rider"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 335,
            "keyword_name" => "startRide",
            "keyword_value" => "Start Ride"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 334,
            "keyword_name" => "endRide",
            "keyword_value" => "End Ride"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 333,
            "keyword_name" => "pleaseSelectExtraCharges",
            "keyword_value" => "Please select extra charges"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 332,
            "keyword_name" => "applyExtraCharges",
            "keyword_value" => "Apply extra charges"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 318,
            "keyword_name" => "areYouCertainOffline",
            "keyword_value" => "Are you sure you want to switch to offline mode?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 319,
            "keyword_name" => "areYouCertainOnline",
            "keyword_value" => "Are you sure you want to go online?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 310,
            "keyword_name" => "offLine",
            "keyword_value" => "Offline"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 311,
            "keyword_name" => "online",
            "keyword_value" => "Online"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 276,
            "keyword_name" => "addExtraCharges",
            "keyword_value" => "Add Extra Charges"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 277,
            "keyword_name" => "enterAmount",
            "keyword_value" => "Add Extra Charges"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 278,
            "keyword_name" => "pleaseAddAmount",
            "keyword_value" => "Please add amount"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 279,
            "keyword_name" => "title",
            "keyword_value" => "Title"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 280,
            "keyword_name" => "saveCharges",
            "keyword_value" => "Save Charges"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 281,
            "keyword_name" => "youAreOnlineNow",
            "keyword_value" => "You are online now"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 282,
            "keyword_name" => "youAreOfflineNow",
            "keyword_value" => "You are offline now"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 283,
            "keyword_name" => "requests",
            "keyword_value" => "Request"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 284,
            "keyword_name" => "areYouSureYouWantToCancelThisRequest",
            "keyword_value" => "Are you certain that you wish to cancel this request?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 285,
            "keyword_name" => "decline",
            "keyword_value" => "Decline"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 286,
            "keyword_name" => "accept",
            "keyword_value" => "Accept"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 287,
            "keyword_name" => "areYouSureYouWantToAcceptThisRequest",
            "keyword_value" => "Please confirm if you would like to proceed with this request."
          ],
          [
            "screenId" => "34",
            "keyword_id" => 288,
            "keyword_name" => "call",
            "keyword_value" => "Call"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 289,
            "keyword_name" => "areYouSureYouWantToArriving",
            "keyword_value" => "Are you sure you want to arriving?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 290,
            "keyword_name" => "areYouSureYouWantToArrived",
            "keyword_value" => "Are you sure you want to arrived?"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 291,
            "keyword_name" => "enterOtp",
            "keyword_value" => "Enter OTP"
          ],
          [
            "screenId" => "34",
            "keyword_id" => 292,
            "keyword_name" => "pleaseEnterValidOtp",
            "keyword_value" => "Please enter valid otp"
          ]
        ]
      ],
      [
        "screenID" => "35",
        "ScreenName" => "Driver Register Screen",
        "keyword_data" => [
          [
            "screenId" => "35",
            "keyword_id" => 293,
            "keyword_name" => "pleaseSelectService",
            "keyword_value" => "Please Select Service"
          ],
          [
            "screenId" => "35",
            "keyword_id" => 294,
            "keyword_name" => "userDetail",
            "keyword_value" => "User Detail"
          ],
          [
            "screenId" => "35",
            "keyword_id" => 295,
            "keyword_name" => "selectService",
            "keyword_value" => "Select Service"
          ],
          [
            "screenId" => "35",
            "keyword_id" => 296,
            "keyword_name" => "carColor",
            "keyword_value" => "Car Color"
          ],
          [
            "screenId" => "35",
            "keyword_id" => 297,
            "keyword_name" => "carPlateNumber",
            "keyword_value" => "Car Plate Number"
          ],
          [
            "screenId" => "35",
            "keyword_id" => 298,
            "keyword_name" => "carProductionYear",
            "keyword_value" => "Car Production Year"
          ]
        ]
      ],
      [
        "screenID" => "36",
        "ScreenName" => "Vehicle Screen",
        "keyword_data" => [
          [
            "screenId" => "36",
            "keyword_id" => 343,
            "keyword_name" => "updateVehicleInfo",
            "keyword_value" => "Vehicle Details"
          ],
          [
            "screenId" => "36",
            "keyword_id" => 315,
            "keyword_name" => "vehicleInfoUpdateSucessfully",
            "keyword_value" => "Vehicle info update sucessfully"
          ],
          [
            "screenId" => "36",
            "keyword_id" => 314,
            "keyword_name" => "youCannotChangeService",
            "keyword_value" => "You Cannot Change service"
          ],
          [
            "screenId" => "36",
            "keyword_id" => 313,
            "keyword_name" => "serviceInfo",
            "keyword_value" => "Service Info"
          ],
          [
            "screenId" => "36",
            "keyword_id" => 299,
            "keyword_name" => "updateVehicle",
            "keyword_value" => "Update Vehicle Detail"
          ]
        ]
      ],
      [
        "screenID" => "37",
        "ScreenName" => "Document Screen",
        "keyword_data" => [
          [
            "screenId" => "37",
            "keyword_id" => 341,
            "keyword_name" => "fileSizeValidateMsg",
            "keyword_value" => "File too large to upload"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 337,
            "keyword_name" => "documents",
            "keyword_value" => "Documents"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 336,
            "keyword_name" => "file",
            "keyword_value" => "File"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 317,
            "keyword_name" => "someRequiredDocumentAreNotUploaded",
            "keyword_value" => "Some required document are not uploaded. Please upload all required documents."
          ],
          [
            "screenId" => "37",
            "keyword_id" => 316,
            "keyword_name" => "isMandatoryDocument",
            "keyword_value" => "* is a mandatory document."
          ],
          [
            "screenId" => "37",
            "keyword_id" => 300,
            "keyword_name" => "userNotApproveMsg",
            "keyword_value" => "Your profile is currently being reviewed. Please wait for some time or get in touch with your administrator."
          ],
          [
            "screenId" => "37",
            "keyword_id" => 301,
            "keyword_name" => "uploadFileConfirmationMsg",
            "keyword_value" => "Are you sure you want to upload this file?"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 302,
            "keyword_name" => "selectDocument",
            "keyword_value" => "Select Document"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 303,
            "keyword_name" => "addDocument",
            "keyword_value" => "Add Document"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 304,
            "keyword_name" => "areYouSureYouWantToDeleteThisDocument",
            "keyword_value" => "Are you sure you want to delete this document?"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 305,
            "keyword_name" => "expireDate",
            "keyword_value" => "Expire Date"
          ],
          [
            "screenId" => "37",
            "keyword_id" => 306,
            "keyword_name" => "goDashBoard",
            "keyword_value" => "Go DashBoard"
          ]
        ]
      ],
      [
        "screenID" => "38",
        "ScreenName" => "Earnings Screen",
        "keyword_data" => [
          [
            "screenId" => "38",
            "keyword_id" => 339,
            "keyword_name" => "noteSelectFromDate",
            "keyword_value" => "Note: Select From To Date"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 338,
            "keyword_name" => "earnings",
            "keyword_value" => "Earnings"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 321,
            "keyword_name" => "totalEarning",
            "keyword_value" => "Total Earning"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 322,
            "keyword_name" => "pleaseSelectFromDateAndToDate",
            "keyword_value" => "Please select from date and to date"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 323,
            "keyword_name" => "fromDate",
            "keyword_value" => "From Date"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 324,
            "keyword_name" => "toDate",
            "keyword_value" => "To Date"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 325,
            "keyword_name" => "weeklyOrderCount",
            "keyword_value" => "Weekly Order Count"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 326,
            "keyword_name" => "today",
            "keyword_value" => "Today"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 327,
            "keyword_name" => "weekly",
            "keyword_value" => "Weekly"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 328,
            "keyword_name" => "report",
            "keyword_value" => "Report"
          ],
          [
            "screenId" => "38",
            "keyword_id" => 329,
            "keyword_name" => "todayEarning",
            "keyword_value" => "Today Earning"
          ]
        ]
      ],
      [
        "screenID" => "39",
        "ScreenName" => "Bid Listing Screen",
        "keyword_data" => [
          [
            "screenId" => "39",
            "keyword_id" => 370,
            "keyword_name" => "bid_book",
            "keyword_value" => "Book By Bid"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 371,
            "keyword_name" => "or",
            "keyword_value" => "OR"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 372,
            "keyword_name" => "or",
            "keyword_value" => "OR"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 373,
            "keyword_name" => "place_bid",
            "keyword_value" => "Place Bid"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 374,
            "keyword_name" => "place_your_bid",
            "keyword_value" => "Place Your Bid"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 375,
            "keyword_name" => "note_optional",
            "keyword_value" => "Note (optional)"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 376,
            "keyword_name" => "bid_under_review",
            "keyword_value" => "Bid Under Review"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 377,
            "keyword_name" => "bid_under_review_note",
            "keyword_value" => "Please wait while the rider reviews and accepts your bid."
          ],
          [
            "screenId" => "39",
            "keyword_id" => 378,
            "keyword_name" => "no_bids_note",
            "keyword_value" => "No bids have been submitted by drivers yet. They will be displayed here once available."
          ],
          [
            "screenId" => "39",
            "keyword_id" => 379,
            "keyword_name" => "cancel_my_bid",
            "keyword_value" => "Cancel My Bid"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 368,
            "keyword_name" => "bids",
            "keyword_value" => "Bids"
          ],
          [
            "screenId" => "39",
            "keyword_id" => 369,
            "keyword_name" => "bid_for_ride",
            "keyword_value" => "Bid For Ride"
          ]
        ]
      ]
    ];


    foreach ($screen_data as $screen) {
      $screen_record = Screen::where('screenID', $screen['screenID'])->first();

      if ($screen_record == null) {
        $screen_record = Screen::create([
          'screenId'   => $screen['screenID'],
          'screenName' => $screen['ScreenName']
        ]);
      }

      if (isset($screen['keyword_data']) && count($screen['keyword_data']) > 0) {
        foreach ($screen['keyword_data'] as $keyword_data) {
          $keyword_record = DefaultKeyword::where('keyword_id', $keyword_data['keyword_id'])->first();

          if ($keyword_record == null) {
            $keyword_record = DefaultKeyword::create([
              'screen_id' => $screen_record['screenId'],
              'keyword_id' => $keyword_data['keyword_id'],
              'keyword_name' => $keyword_data['keyword_name'],
              'keyword_value' => $keyword_data['keyword_value']
            ]);
          }
        }
      }
    }
  }
}
