<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use App\Http\Controllers\API;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('register',[API\UserController::class, 'register']);
Route::post('driver-register',[API\UserController::class, 'driverRegister']);
Route::post('/driver/register/step-one', [API\UserController::class, 'validateDriverStepOne']);
Route::post('login',[API\UserController::class,'login']);
Route::post('forget-password',[ API\UserController::class,'forgetPassword']);
Route::post('social-login',[ API\UserController::class, 'socialLogin' ]);
Route::get('user-list',[API\UserController::class, 'userList']);
Route::get('user-detail',[API\UserController::class, 'userDetail']);
Route::post('send-otp', [API\UserController::class, 'sendOtp']);
Route::post('verify-otp', [API\UserController::class, 'verifyOtp']);
Route::get('document-list', [ API\DocumentController::class, 'getList' ] );

Route::get('service-list', [ API\ServiceController::class, 'getList' ]);
Route::post('estimate-price-time', [ API\ServiceController::class, 'estimatePriceTime' ]);
Route::get('appsetting', [ API\DashboardController::class, 'appsetting'] );
Route::get('near-by-driver',[ App\Http\Controllers\HomeController::class, 'driverListMap' ]);
Route::get('language-table-list', [API\LanguageTableController::class, 'getList']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('driver-document-list', [ API\DriverDocumentController::class, 'getList' ] );
    Route::post('driver-document-save', [ App\Http\Controllers\DriverDocumentController::class, 'store' ] );
    Route::post('driver-document-update/{id}', [ App\Http\Controllers\DriverDocumentController::class, 'update' ] );
    Route::post('driver-document-delete/{id}', [ App\Http\Controllers\DriverDocumentController::class, 'destroy' ] );

    Route::post('verify-coupon', [ API\RideRequestController::class, 'verifyCoupon'] );
    Route::post('save-riderequest', [ App\Http\Controllers\RideRequestController::class, 'store'] );
    Route::post('riderequest-update/{id}', [ App\Http\Controllers\RideRequestController::class, 'update' ] );
    Route::get('riderequest-list', [ API\RideRequestController::class, 'getList'] );
    Route::get('riderequest-detail', [ API\RideRequestController::class, 'getDetail'] );
    Route::post('riderequest-delete/{id}', [ App\Http\Controllers\RideRequestController::class, 'destroy' ] );
    Route::get('coupon-list', [ API\CouponController::class, 'getList'] );

    Route::post('apply-bid', [ App\Http\Controllers\RideRequestController::class, 'applyBidRideRequest'] );
    Route::post('get-bidding-riderequest', [ App\Http\Controllers\RideRequestController::class, 'getBiddingDrivers'] );
    Route::post('riderequest-bid-respond', [ App\Http\Controllers\RideRequestController::class, 'acceptBidRequest'] );

    Route::post('riderequest/{id}/drop/{index}', [App\Http\Controllers\RideRequestController::class, 'updateDropLocationTime']);
    Route::post('riderequest-respond', [ App\Http\Controllers\RideRequestController::class, 'acceptRideRequest' ] );
    Route::post('complete-riderequest', [ API\RideRequestController::class, 'completeRideRequest' ] );

    Route::get('wallet-detail', [ API\WalletController::class, 'getWallatDetail'] );
    Route::post('save-wallet', [ API\WalletController::class, 'saveWallet'] );
    Route::get('wallet-list', [ API\WalletController::class, 'getList'] );
    Route::post('notification-list', [ API\NotificationController::class, 'getList'] );
    Route::get('notification-counts',[ API\NotificationController::class ,'notificationCounts']);
    Route::patch('notifications/{id}/read',[ API\NotificationController::class ,'markAsRead']); // Mark a Single Notification as Read
    Route::patch('notifications/read-all', [API\NotificationController::class, 'markAllAsRead']); // Mark All Notifications as Read

    Route::get('payment-gateway-list', [ API\PaymentGatewayController::class, 'getList'] );

    Route::get('sos-list', [ API\SosController::class, 'getList'] );
    Route::post('save-sos', [ App\Http\Controllers\SosController::class, 'store'] );
    Route::post('sos-update/{id}', [ App\Http\Controllers\SosController::class, 'update' ] );
    Route::post('sos-delete/{id}', [ App\Http\Controllers\SosController::class, 'destroy'] );
    Route::post('admin-sos-notify', [ API\SosController::class, 'adminSosNotify'] );

    Route::post('save-ride-rating', [ API\RideRequestController::class, 'rideRating'] );
    
    Route::post('save-payment', [ API\PaymentController::class, 'paymentSave'] );

    Route::get('withdrawrequest-list', [ API\WithdrawRequestController::class, 'getList'] );
    Route::post('save-withdrawrequest', [ App\Http\Controllers\WithdrawRequestController::class, 'store'] );
    Route::post('update-status/{id}', [ App\Http\Controllers\WithdrawRequestController::class, 'updateStatus' ] );

    Route::post('save-complaint', [ App\Http\Controllers\ComplaintController::class, 'store'] );
    Route::post('update-complaint/{id}', [ App\Http\Controllers\ComplaintController::class, 'update'] );

    Route::get('complaintcomment-list', [ API\ComplaintCommentController::class, 'getList'] );
    Route::post('save-complaintcomment', [ App\Http\Controllers\ComplaintCommentController::class, 'store'] );
    Route::post('update-complaintcomment/{id}', [ App\Http\Controllers\ComplaintCommentController::class, 'update' ] );

    Route::get('admin-dashboard', [ API\DashboardController::class, 'adminDashboard'] );
    Route::get('rider-dashboard', [ API\DashboardController::class, 'riderDashboard'] );

    Route::get('current-riderequest', [ API\DashboardController::class, 'currentRideRequest'] );

    Route::post('earning-list', [ API\PaymentController::class, 'DriverEarningList'] );
    
    Route::post('update-profile', [ API\UserController::class, 'updateProfile']);
    Route::post('change-password',[ API\UserController::class, 'changePassword']);
    Route::post('update-user-status', [ API\UserController::class, 'updateUserStatus']);
    
    Route::post('delete-user-account', [ API\UserController::class, 'deleteUserAccount']);

    Route::get('additional-fees-list', [ API\AdditionalFeesController::class, 'getList'] );
    Route::get('logout',[ API\UserController::class, 'logout']);

    Route::post('setting-upload-image', [App\Http\Controllers\SettingController::class, 'settingUploadImage']);
    Route::post('save-setting', [ App\Http\Controllers\SettingController::class, 'settingUpdate'] );
    Route::get('get-setting', [API\SettingController::class, 'getList']);
    Route::get('frontenddata-list',[API\FrontendDataController::class,'getList']);
    Route::post('frontenddata-save',[ App\Http\Controllers\FrontendDataController::class, 'store' ]);
    Route::get('get-appsetting', [API\UserController::class, 'getAppSetting']);
    Route::post('update-appsetting', [App\Http\Controllers\SettingController::class, 'updateAppSetting']);
    
    // Loyalty Points
    Route::get('point-detail', [ API\PointController::class, 'getPointDetail'] );
    Route::get('point-list', [ API\PointController::class, 'getList'] );
    Route::post('point-withdraw', [ API\PointController::class, 'pointWithdraw'] );

    // Ride schedule
    Route::post('schedule-riderequest', [ App\Http\Controllers\RideRequestController::class, 'saveScheduleRide'] );
    Route::get('getScheduleRidesList', [ API\RideRequestController::class, 'getScheduleRidesList'] );
    Route::post('cancel-scheduled-ride/{id}', [App\Http\Controllers\RideRequestController::class, 'cancelScheduledRide']);
    Route::post('accept-schedule-ride/{id}', [App\Http\Controllers\RideRequestController::class, 'acceptScheduleRide']);
    Route::post('decline-accepted-schedule-ride/{id}', [App\Http\Controllers\RideRequestController::class, 'declineAcceptedScheduleRide']);

    // Driver messages
    Route::get('driver-message-list', [ API\SettingController::class, 'getDriverMessageList' ] );

    // Stripe
    Route::post('/create-setup-intent', [API\StripeCardController::class, 'createSetupIntent']);
    Route::get('/list-cards', [API\StripeCardController::class, 'listPaymentMethods']);
    Route::delete('/delete-card', [API\StripeCardController::class, 'deletePaymentMethod']);
    Route::post('/set-default-card', [API\StripeCardController::class, 'setDefaultCard']);
    Route::post('/create-payment-intent', [API\StripeCardController::class, 'createPaymentIntent']);
    Route::post('/capture-payment-intent', [API\StripeCardController::class, 'capturePaymentIntent']);
    Route::post('/cancel-payment-intent', [API\StripeCardController::class, 'cancelPaymentIntent']);
    Route::get('/transactions-list', [ API\StripeCardController::class, 'getTransactionsList'] );

    Route::post('/create-stripe-customer', [API\StripeCardController::class, 'createStripeCustomer']);

    // surge price
    Route::get('surge-price-list', [ API\SurgePriceController::class, 'getList'] );

    // send notification for in app chat
    Route::post('send-notification', [ API\NotificationController::class, 'sendNotification']);

    // get user recent ride destination location
    Route::get('rider-recent-ride-location', [ API\UserController::class, 'riderRecentRideLocation']);

    // get user address list
    Route::get('user-address-list', [ API\UserController::class, 'getUserAddressList']);

    //  paypal payment flow
    Route::post('wallet/paypal/create-payment', [API\PaypalController::class, 'createPayment']);
    Route::post('wallet/paypal/capture-payment', [API\PaypalController::class, 'capturePayment']);
    Route::get('wallet/paypal/saved-cards', [API\PaypalController::class, 'getSavedCards']);
    Route::delete('wallet/paypal/saved-cards/{id}', [API\PaypalController::class, 'deleteCard']);

        // Paystack
    Route::post('/paystack-initialize-payment', [API\PaystackController::class, 'initializePayment']);
    Route::post('/paystack-verify-payment', [API\PaystackController::class, 'verifyPayment']);

        // ── Cash-In (Rider) – /api/mobile-money/cashin/* ────────────────────
    Route::prefix('mobile-money/cashin')->group(function () {
        Route::get('services', [Api\CashInController::class, 'getServices']);
        Route::get('service-details', [Api\CashInController::class, 'getServiceDetails']);
        Route::post('quote', [Api\CashInController::class, 'getQuote']);
        Route::post('collect', [Api\CashInController::class, 'collectPayment']);
        Route::get('verify', [Api\CashInController::class, 'verifyTransaction']);
    });

    // ── Cash-Out (Driver) – /api/mobile-money/cashout/* ─────────────────
    Route::prefix('mobile-money/cashout')->group(function () {
        Route::post('initiate', [Api\CashOutController::class, 'initiate']);
        Route::get('verify', [Api\CashOutController::class, 'verify']);
    });
});

Route::get('place-autocomplete-api', [ API\RideRequestController::class, 'placeAutoComplete' ] );
Route::get('place-detail-api', [ API\RideRequestController::class, 'placeDetail' ] );

// get car models
Route::get('car-models-list', [ API\CarModelController::class, 'getList'] );
// PayPal Webhook & Return redirects (Public)
Route::post('paypal/webhook', [API\PaypalController::class, 'webhook']);
Route::get('paypal/return', [API\PaypalController::class, 'returnCapture']);

Route::post('mobile-money/webhook', [Api\MobileMoneyController::class, 'mobileMoneyWebhook']);
