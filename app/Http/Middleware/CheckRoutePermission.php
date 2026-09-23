<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRoutePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if($user->user_type == 'admin'){
            return $next($request);
        }else{
            $routeName = $request->route()->getName();
            if(!$routeName) {
                return $next($request); 
            }

            $permissionName = self::mapRouteToPermission($routeName);
            //dd($permissionName);

            if ($user && (
                $user->can($permissionName) ||
                $permissionName === 'home' 
            )) {
                return $next($request);
            }
            abort(403, 'You do not have permission to access this page.');
        }
    }

    private static function mapRouteToPermission($routeName)
    {
        //dd($routeName);
        $actionMap = [
            'index'   => 'list',
            'create'  => 'add',
            'store'   => 'add',
            'edit'    => 'edit',
            'update'  => 'edit',
            'destroy' => 'delete',
            'show'    => 'show',
        ];
    
        $customMappings = [
            // Term & Privacy pages
            'term-condition' => 'terms condition',
            'term-condition-save' => 'terms condition',
            'privacy-policy' => 'privacy policy',
            'privacy-policy-save' => 'privacy policy',

            // Settings
            'setting.index' => 'home',
            'layout_page' => 'home',
            'settingsUpdates' => 'setting edit',
            'AppSetting' => 'setting edit',
            'settingUpdate' => 'setting edit',
            'paymentSettingsUpdate' => 'setting edit',
            'walletSettingsUpdate' => 'setting edit',
            'rideSettingsUpdate' => 'setting edit',
            'notificationSettingsUpdate' => 'setting edit',
            'envSetting' => 'setting edit',
            'updateProfile' => 'setting edit',
            'changePassword' => 'home',

            // Language
            'getLanguageFile' => 'languagelist-list',
            'saveLangContent' => 'languagelist-edit',
            'import.languagewithkeyword' => 'languagewithkeyword-edit',
            'bulk.language.data' => 'bulkimport-list',
            'download.language.with,keyword.list' => 'languagewithkeyword-list',
            'download.template' => 'bulkimport-list',
            'help' => 'bulkimport-list',

            // Notification
            'notification.list' => 'pushnotification list',
            'notification.counts' => 'home',
            'notification.index' => 'pushnotification list',

            // Complaints
            'complaintcomment.store' => 'complaint add',
            'complaintcomment.update' => 'complaint edit',

            // Pages
            'Pages-edit.edit' => 'pages edit',

            // Map & Drivers
            'map' => 'driver location',
            'driver.search' => 'driver show',
            'driverDetail' => 'driver show',
            'driver_list.map' => 'driver location',
            'heatmap' => 'heatmap list',

            // Reports
            'adminEarningReport' => 'driverearning list',
            'driver.earning.report' => 'driverearning list',
            'driver.report.list' => 'driver list',
            'service.wise.report' => 'service-wise-report',
            'download-admin-earning' => 'driverearning list',
            'download-driver-earning' => 'driverearning list',
            'download.driver.report' => 'driver list',
            'download.servicewise.report' => 'service-wise-report',
            'download-adminearningpdf' => 'driverearning list',
            'download-driverearningpdf' => 'driverearning list',
            'download.driver.report.pdf' => 'driver list',
            'download.servicewise.report.pdf' => 'service-wise-report',

            // Withdraw
            'download.withdrawrequest.list' => 'withdrawrequest list',

            // Wallet/Points
            'savewallet.fund' => 'rider add',
            'savepoints.fund' => 'rider add',

            // Others            
            'changeStatus' => 'home',
            'remove.file' => 'home',
            'assign-driver' => 'home',
            'frontend.website.form' => 'home',
            'frontend.website.information.update' => 'home',
            'datatble.destroySelected' => 'home',
            'home' => 'home',
            'updateProfile' => 'home',

            // extras 
            'driver.pending' => 'pending driver',
            'screen.index' => 'screen-list',
            'defaultkeyword.index'   => 'defaultkeyword-list',
            'defaultkeyword.create'  => 'defaultkeyword-add',
            'defaultkeyword.store'   => 'defaultkeyword-add',
            'defaultkeyword.edit'    => 'defaultkeyword-edit',
            'defaultkeyword.update'  => 'defaultkeyword-edit',
            'defaultkeyword.destroy' => 'defaultkeyword-delete',
            'defaultkeyword.show'    => 'defaultkeyword-show',
            'languagelist.index'   => 'languagelist-list',
            'languagelist.create'  => 'languagelist-add',
            'languagelist.store'   => 'languagelist-add',
            'languagelist.edit'    => 'languagelist-edit',
            'languagelist.update'  => 'languagelist-edit',
            'languagelist.destroy' => 'languagelist-delete',
            'languagelist.show'    => 'languagelist-show',
            'languagewithkeyword.index'   => 'languagewithkeyword-list',
            'languagewithkeyword.create'  => 'languagewithkeyword-add',
            'languagewithkeyword.store'   => 'languagewithkeyword-add',
            'languagewithkeyword.edit'    => 'languagewithkeyword-edit',
            'languagewithkeyword.update'  => 'languagewithkeyword-edit',
            'languagewithkeyword.destroy' => 'languagewithkeyword-delete',
            'languagewithkeyword.show'    => 'languagewithkeyword-show',
            'permission.save'             => 'permission add',
            'referralSettingsUpdate'      => 'setting edit',
            'getLanguageDriverMessage'    => 'setting list',
            'saveLanguageDriverMessage'   => 'setting edit',
        ];
        
    
        //dd($customMappings[$routeName]);
        // If a full route name matches directly
        if (isset($customMappings[$routeName])) {
            return $customMappings[$routeName];
        }

        //dd($customMappings[$routeName]);
    
        // Handle resource-like routes, e.g. rider.index
        $parts = explode('.', $routeName);

        //dd($parts);
    
        if (count($parts) === 2) {
            [$module, $action] = $parts;
            $module = str_replace('-', '', $module);
    
            if ($module === 'surgeprices') {
                $module = 'surgeprice';
            }

            if ($module === 'useraddress') {
                $module = 'rider';
            }
            
    
            $permissionAction = $actionMap[$action] ?? $action;
            return "$module $permissionAction";
        }
    
        return '';
    }
}
