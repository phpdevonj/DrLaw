<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\RideRequestController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\AdditionalFeesController;
use App\Http\Controllers\ClientTestimonialsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DriverDocumentController;
use App\Http\Controllers\SosController;
use App\Http\Controllers\WithdrawRequestController;

use App\Http\Controllers\ComplaintCommentController;
use App\Http\Controllers\DefaultkeywordController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PushNotificationController;

use App\Http\Controllers\DispatchController;
use App\Http\Controllers\Frontendwebsite\FrontendController;
use App\Http\Controllers\LanguageListController;
use App\Http\Controllers\LanguageWithKeywordListController;
use App\Http\Controllers\OurMissionController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\SurgePriceController;
use App\Http\Controllers\WhyChooseController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__.'/auth.php';


Route::get('/mqtt/publish/{topic}/{message}', [ HomeController::class, 'SendMsgViaMqtt' ]);
Route::get('/mqtt/subscribe/{topic}', [ HomeController::class, 'SubscribetoTopic' ]);

//Auth pages Routs
Route::group(['prefix' => 'auth'], function() {
    Route::get('login', [HomeController::class, 'authLogin'])->name('auth.login');
    Route::get('register', [HomeController::class, 'authRegister'])->name('auth.register');
    Route::get('recover-password', [HomeController::class, 'authRecoverPassword'])->name('auth.recover-password');
    Route::get('confirm-email', [HomeController::class, 'authConfirmEmail'])->name('auth.confirm-email');
    Route::get('lock-screen', [HomeController::class, 'authlockScreen'])->name('auth.lock-screen');
});

Route::get('ride-invoice/{id}', [RideRequestController::class, 'rideInvoicePdf'])->name('ride-invoice');
Route::get('language/{locale}', [ HomeController::class, 'changeLanguage'])->name('change.language');
Route::group(['middleware' => ['auth', 'verified', 'admin']], function()
{
    // Route::get('/', [HomeController::class, 'index']);
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::group(['namespace' => '' ], function () {
        Route::resource('permission', PermissionController::class);
        Route::get('permission/add/{type}',[ PermissionController::class,'addPermission' ])->name('permission.add');
        Route::post('permission/save',[ PermissionController::class,'savePermission' ])->name('permission.save');
	});

	Route::resource('role', RoleController::class);
	Route::resource('region', RegionController::class);
	Route::resource('service', ServiceController::class);

	Route::resource('rider', RiderController::class);
	Route::resource('driver', DriverController::class);
    Route::get('driver/list/{status?}', [ DriverController::class,'index' ])->name('driver.pending');

	Route::resource('fleet', FleetController::class);
	Route::resource('additionalfees', AdditionalFeesController::class);
	Route::resource('document', DocumentController::class);
	Route::resource('driverdocument', DriverDocumentController::class);


    Route::resource('riderequest', RideRequestController::class)->except(['create', 'edit']);
    Route::resource('coupon', CouponController::class);
    Route::resource('complaint', ComplaintController::class);
    Route::resource('surge-prices', SurgePriceController::class);
    Route::resource('sos', SosController::class);
    Route::resource('withdrawrequest', WithdrawRequestController::class);
    Route::post('withdrawrequest/status', [ WithdrawRequestController::class, 'updateStatus' ] )->name('withdraw.request.status');
    Route::get('bank-detail/{id}', [ WithdrawRequestController::class, 'userBankDetail' ] )->name('bankdetail');


    Route::post('complaintcomment-save', [ ComplaintCommentController::class, 'store'] )->name('complaintcomment.store');
    Route::post('complaintcomment-update/{id}', [ ComplaintCommentController::class, 'update' ] )->name('complaintcomment.update');

	Route::get('changeStatus', [ HomeController::class, 'changeStatus'])->name('changeStatus');

	Route::get('setting/{page?}',[ SettingController::class, 'settings'])->name('setting.index');
    Route::post('/layout-page',[ SettingController::class, 'layoutPage'])->name('layout_page');
    Route::post('settings/save',[ SettingController::class , 'settingsUpdates'])->name('settingsUpdates');
    Route::post('appsetting/save',[ SettingController::class , 'AppSetting'])->name('AppSetting');
    Route::post('mobile-config-save',[ SettingController::class , 'settingUpdate'])->name('settingUpdate');
    Route::post('payment-settings/save',[ SettingController::class , 'paymentSettingsUpdate'])->name('paymentSettingsUpdate');
    Route::post('wallet-settings/save',[ SettingController::class , 'walletSettingsUpdate'])->name('walletSettingsUpdate');
    Route::post('ride-settings/save',[ SettingController::class , 'rideSettingsUpdate'])->name('rideSettingsUpdate');
    Route::post('notification-settings/save',[ SettingController::class , 'notificationSettingsUpdate'])->name('notificationSettingsUpdate');

    Route::post('get-lang-file', [ LanguageController::class, 'getFile' ] )->name('getLanguageFile');
    Route::post('save-lang-file', [ LanguageController::class, 'saveFileContent' ] )->name('saveLangContent');

    Route::get('pages/term-condition',[ SettingController::class, 'termAndCondition'])->name('term-condition');
    Route::post('term-condition-save',[ SettingController::class, 'saveTermAndCondition'])->name('term-condition-save');

    Route::get('pages/privacy-policy',[ SettingController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::post('privacy-policy-save',[ SettingController::class, 'savePrivacyPolicy'])->name('privacy-policy-save');

	Route::post('env-setting', [ SettingController::class , 'envChanges'])->name('envSetting');
    Route::post('update-profile', [ SettingController::class , 'updateProfile'])->name('updateProfile');
    Route::post('change-password', [ SettingController::class , 'changePassword'])->name('changePassword');

    Route::get('notification-list',[ NotificationController::class ,'notificationList'])->name('notification.list');
    Route::get('notification-counts',[ NotificationController::class ,'notificationCounts'])->name('notification.counts');
    Route::get('notification',[ NotificationController::class ,'index'])->name('notification.index');

    Route::post('remove-file',[ HomeController::class, 'removeFile' ])->name('remove.file');
    Route::get('mapview',[ HomeController::class, 'map' ])->name('map');
    Route::get('map-view',[ HomeController::class, 'driverListMap' ])->name('driver_list.map');
    // Route::get('driver-detail', [ HomeController::class, 'driverDetail' ] )->name('driverdetail');

    Route::get('driver/{id}/details', [HomeController::class, 'driverDetail'])->name('driverDetail');
    Route::get('driver/searchById/{id}', [HomeController::class, 'search'])->name('driver.search');

    Route::post('save-wallet-fund/{user_id}', [ HomeController::class, 'saveWalletHistory'] )->name('savewallet.fund');

    Route::resource('pushnotification', PushNotificationController::class);

    Route::resource('dispatch', DispatchController::class)->except(['index', 'edit']);

    // Route::get('informations', [SettingController::class, 'information'])->name('information');
    // Route::get('dowloandapp', [SettingController::class, 'downloandapp'])->name('downloandapp');
    // Route::get('contactinfo', [SettingController::class, 'contactinfo'])->name('contactinfo');
    // Route::post('setting-upload-image', [SettingController::class, 'settingUploadImage'])->name('image-save');

    Route::get('website-section/{type}', [ FrontendController::class, 'websiteSettingForm' ] )->name('frontend.website.form');
    Route::post('update-website-information/{type}', [ FrontendController::class, 'websiteSettingUpdate' ] )->name('frontend.website.information.update');

    //pages
    Route::resource('pages', PagesController::class);
    Route::get('pages-edit/{id?}', [PagesController::class, 'edit'])->name('Pages-edit.edit');

	Route::resource('our-mission', OurMissionController::class);
	Route::resource('why-choose', WhyChooseController::class);
    Route::resource('client-testimonials', ClientTestimonialsController::class);
    // Route::get('delete/{id}', [OurMissionController::class, 'destroy'])->name('data-delete');

    Route::resource('screen', ScreenController::class);
    Route::resource('defaultkeyword', DefaultkeywordController::class);
    Route::resource('languagelist', LanguageListController::class);
    Route::resource('languagewithkeyword', LanguageWithKeywordListController::class);
    Route::get('download-language-with-keyword-list', [LanguageWithKeywordListController::class, 'downloadLanguageWithKeywordList'])->name('download.language.with,keyword.list');

    Route::post('import-language-keyword', [LanguageWithKeywordListController::class, 'importlanguagewithkeyword'])->name('import.languagewithkeyword');
    Route::get('bulklanguagedata', [LanguageWithKeywordListController::class, 'bulklanguagedata'])->name('bulk.language.data');
    Route::get('help', [LanguageWithKeywordListController::class, 'help'])->name('help');
    Route::get('download-template', [LanguageWithKeywordListController::class, 'downloadtemplate'])->name('download.template');

    Route::delete('datatble/destroySelected', [HomeController::class, 'destroySelected'])->name('datatble.destroySelected');

    
    // report data Route
    Route::get('admin-earning-report', [ReportController::class, 'adminEarning'])->name('adminEarningReport');
    Route::get('driver-earning-report', [ ReportController::class, 'driverEarning' ])->name('driver.earning.report');
    Route::get('driver-report-report', [ ReportController::class, 'driverReport' ])->name('driver.report.list');
    Route::get('service-wise-report', [ ReportController::class, 'serviceWiseReport' ])->name('serviceWiseReport');

    // Report Excel Route
    Route::get('download-admin-earning', [ReportController::class, 'downloadAdminEarning'])->name('download-admin-earning');
    Route::get('download-driver-earning', [ReportController::class, 'downloadDriverEarning'])->name('download-driver-earning');
    Route::get('download-driver-report', [ReportController::class, 'downloadDriverReport'])->name('download.driver.report');
    Route::get('servicewise-report-export', [ReportController::class, 'serviceWiseReportExport'])->name('download.servicewise.report');

    //Report Pdf Route
    Route::get('download-adminearningpdf', [ReportController::class, 'downloadAdminEarningPdf'])->name('download-adminearningpdf');
    Route::get('download-driverearningpdf', [ReportController::class, 'downloadDriverEarningPdf'])->name('download-driverearningpdf');
    Route::get('download-driver-report-pdf', [ReportController::class, 'downloadDriverReportPdf'])->name('download.driver.report.pdf');
    Route::get('servicewise-report-pdf-export', [ReportController::class, 'serviceWiseReportPdfExport'])->name('download.servicewise.report.pdf');

    Route::get('download-withdrawrequest-list', [ WithdrawRequestController::class, 'downloadWithdrawRequestList'])->name('download.withdrawrequest.list');

});

Route::get('/ajax-list',[ HomeController::class, 'getAjaxList' ])->name('ajax-list');

Route::get('/', [FrontendController::class, 'index'])->name('browse');
Route::get('termofservice', [FrontendController::class, 'termofservice'])->name('termofservice');
Route::get('privacypolicy', [FrontendController::class, 'privacypolicy'])->name('privacypolicy');
Route::get('page/{slug}', [FrontendController::class, 'page'])->name('pages');