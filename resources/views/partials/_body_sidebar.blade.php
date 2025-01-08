@php
    $url = '';

    $MyNavBar = \Menu::make('MenuList', function ($menu) use($url){
        
        $menu->add('<span>'.__('message.book_now').'</span>', [ 'class' => '', 'route' => 'dispatch.create'])
                ->prepend('<i class="fa fa-plus"></i>')
                ->data('permission', 'order-add')
                ->link->attr(['class' => '']);

        //Admin Dashboard
        $menu->add('<span>'.__('message.dashboard').'</span>', ['route' => 'home'])
            ->prepend('<i class="fas fa-home"></i>')            
            ->link->attr(['class' => '']); 
        
        $menu->add('<span>'.__('message.rider').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-user"></i>')
            ->nickname('rider')
            ->data('permission', 'rider list')
            ->link->attr(['class' => ''])
            ->href('#rider');

            $menu->rider->add('<span>'.__('message.list_form_title',['form' => __('message.rider')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'rider.index'])
                ->data('permission', 'rider list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->rider->add('<span>'.__('message.add_form_title',['form' => __('message.rider')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'rider.create'])
                ->data('permission', [ 'rider add', 'rider edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.region').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-globe"></i>')
            ->nickname('region')
            ->data('permission', 'region list')
            ->link->attr(['class' => ''])
            ->href('#region');

            $menu->region->add('<span>'.__('message.list_form_title',['form' => __('message.region')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'region.index'])
                ->data('permission', 'region list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->region->add('<span>'.__('message.add_form_title',['form' => __('message.region')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'region.create'])
                ->data('permission', [ 'region add', 'region edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.service').'</span>', [ 'class' => '', 'route' => 'service.index'])
            ->prepend('<i class="fas fa-taxi"></i>')
            ->nickname('service')
            ->data('permission', 'service list')
            ->link->attr(['class' => ''])
            ->href('#service');

            $menu->service->add('<span>'.__('message.list_form_title',['form' => __('message.service')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'service.index'])
                ->data('permission', 'service list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->service->add('<span>'.__('message.add_form_title',['form' => __('message.service')]).'</span>', ['class' => request()->is('service/*/edit') ? 'sidebar-layout active' : 'sidebar-layout','route' => 'service.create'])
                ->data('permission', [ 'service add', 'service edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.driver').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-id-card"></i>')
            ->nickname('driver')
            ->data('permission', 'driver list')
            ->link->attr(['class' => ''])
            ->href('#driver');
            
            $menu->driver->add('<span>'.__('message.list_form_title',['form' => __('message.driver')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'driver.index'])
                ->data('permission', 'driver list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->driver->add('<span>'.__('message.pending_list_form_title',['form' => __('message.driver')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['driver.pending', 'pending'] ])
                ->data('permission', 'driver list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->driver->add('<span>'.__('message.add_form_title',['form' => __('message.driver')]).'</span>', ['class' => request()->is('driver/*/edit') ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driver.create'])
                ->data('permission', [ 'driver add', 'driver edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
            
            $menu->driver->add('<span>'.__('message.manage_driver_document').'</span>', ['class' => ( request()->is('driverdocument') || request()->is('driverdocument/*') ) ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driverdocument.index'])
                ->data('permission', ['driverdocument list'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.document').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-file"></i>')
            ->nickname('document')
            ->data('permission', 'document list')
            ->link->attr(['class' => ''])
            ->href('#document');
            
            $menu->document->add('<span>'.__('message.list_form_title',['form' => __('message.document')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'document.index'])
                ->data('permission', 'document list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->document->add('<span>'.__('message.add_form_title',['form' => __('message.document')]).'</span>', ['class' => request()->is('document/*/edit') ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'document.create'])
                ->data('permission', [ 'document add', 'document edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.coupon').'</span>', [ 'class' => ''])
            ->prepend('<i class="fas fa-gift"></i>')
            ->nickname('coupon')
            ->data('permission', 'coupon list')
            ->link->attr(['class' => ''])
            ->href('#coupon');
            
            $menu->coupon->add('<span>'.__('message.list_form_title',['form' => __('message.coupon')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'coupon.index'])
                ->data('permission', 'coupon list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->coupon->add('<span>'.__('message.add_form_title',['form' => __('message.coupon')]).'</span>', ['class' => request()->is('coupon/*/edit') ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'coupon.create'])
                ->data('permission', [ 'coupon add', 'coupon edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $new_ride_request = App\Models\RideRequest::where('status','new_ride_requested')->count();
        $menu->add('<span>'.__('message.riderequest').'</span>'. ($new_ride_request > 0 ? '<span class="badge badge-dark ride-badge">'.$new_ride_request.'</span>' : '') , [ 'class' => '' ])
            ->prepend('<i class="fas fa-car-side"></i>')
            ->nickname('riderequest')
            ->data('permission', 'riderequest list')
            ->link->attr(['class' => ''])
            ->href('#riderequest');

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.all')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['riderequest.index', 'riderequest_type' => 'all']])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);
            
            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.new_ride')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['riderequest.index', 'riderequest_type' => 'new_ride_requested']])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list mr-1"></i>'.($new_ride_request > 0 ? '<span class="badge badge-dark ride-badge">'.$new_ride_request.'</span>' : ''))
                ->link->attr(['class' => '']);
            
            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.completed')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['riderequest.index', 'riderequest_type' => 'completed']])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.canceled')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['riderequest.index', 'riderequest_type' => 'canceled']])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->riderequest->add('<span>'.__('message.list_form_title',['form' => __('message.pending')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['riderequest.index', 'riderequest_type' => 'pending']])
                ->data('permission', 'riderequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $pending_complaint = App\Models\Complaint::where('status','pending')->count();
        $menu->add('<span>'.__('message.complaint').'</span>'. ($pending_complaint > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_complaint.'</span>' : '') ,[ 'class' => ''])
            ->prepend('<i class="fas fa-file-alt"></i>')
            ->nickname('complaint')
            ->data('permission', 'complaint list')
            ->link->attr(['class' => ''])
            ->href('#complaint');
            
            $menu->complaint->add('<span>'.__('message.list_form_title',['form' => __('message.resolved')]).'</span>', ['class' => 'sidebar-layout' ,'route' => [ 'complaint.index', 'complaint_type' => 'resolved' ]])
                ->data('permission', 'complaint list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->complaint->add('<span>'.__('message.list_form_title',['form' => __('message.pending')]).'</span>', ['class' => 'sidebar-layout' ,'route' => [ 'complaint.index', 'complaint_type' => 'pending' ]])
                ->data('permission', 'complaint list')
                ->prepend('<i class="fas fa-list"></i>'. ($pending_complaint > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_complaint.'</span>' : ''))
                ->link->attr(['class' => '']);

            $menu->complaint->add('<span>'.__('message.list_form_title',['form' => __('message.investigation')]).'</span>', ['class' => 'sidebar-layout' ,'route' => [ 'complaint.index', 'complaint_type' => 'investigation' ]])
                ->data('permission', 'complaint list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.surge_price').'</span>', [ 'class' => '', 'route' => 'surge-prices.index'])
            ->prepend('<i class="fas fa-dollar-sign"></i>')
            ->data('permission', 'surgeprice list')
            ->link->attr(['class' => '']);

        $pending_withdraw_request = App\Models\WithdrawRequest::where('status',0)->count();
        $menu->add('<span>'.__('message.withdrawrequest').'</span>'.($pending_withdraw_request > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_withdraw_request.'</span>' : ''), ['class' => ''])
            ->prepend('<i class="fas fa-money-check"></i>')
            ->nickname('withdrawrequest')
            ->data('permission', 'withdrawrequest list')
            ->link->attr(['class' => ''])
            ->href('#withdrawrequest');

            $menu->withdrawrequest->add('<span>'.__('message.all').'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type' => 'all']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.pending')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'pending']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>'.($pending_withdraw_request > 0 ? '<span class="badge badge-dark ride-badge">'.$pending_withdraw_request.'</span>' : ''))
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.approved')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'approved']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->withdrawrequest->add('<span>'.__('message.list_form_title',['form' => __('message.decline')]).'</span>', ['class' => 'sidebar-layout' ,'route' => ['withdrawrequest.index','withdraw_type'=>'decline']])
                ->data('permission', 'withdrawrequest list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.app_language_setting').'</span>', [ 'class' => ''])
        ->prepend('<i class="fa fa-language"></i>')
        ->nickname('app_language_setting')
        ->data('permission', '')
        ->link->attr(['class' => ''])
        ->href('#app_language_setting');

            $menu->app_language_setting->add('<span>'.__('message.list_form_title',['form' => __('message.screen')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'screen.index'])
                ->data('permission', 'screen-list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->app_language_setting->add('<span>'.__('message.list_form_title',['form' => __('message.default_keyword')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'defaultkeyword.index'])
                    ->data('permission', 'defaultkeyword-list')
                    ->prepend('<i class="fas fa-list"></i>')
                    ->link->attr(['class' => '']);

            $menu->app_language_setting->add('<span>'.__('message.list_form_title',['form' => __('message.language')]).'</span>', ['class' => request()->is('languagelist/*/edit') || request()->is('languagelist/create') ? 'sidebar-layout active' : 'sidebar-layout' ,'route' => 'languagelist.index'])
                    ->data('permission', 'languagelist-list')
                    ->prepend('<i class="fas fa-list"></i>')
                    ->link->attr(['class' => '']);

            $menu->app_language_setting->add('<span>'.__('message.list_form_title',['form' => __('message.language_with_keyword')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'languagewithkeyword.index'])
                    ->data('permission', 'languagewithkeyword-list')
                    ->prepend('<i class="fas fa-list"></i>')
                    ->link->attr(['class' => '']);

            $menu->app_language_setting->add('<span>'.__('message.list_form_title',['form' => __('message.bulk_import_langugage_data')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'bulk.language.data'])
                    ->data('permission', 'bulkimport-list')
                    ->prepend('<i class="fas fa-list"></i>')
                    ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.account_setting').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-users-cog"></i>')
            ->nickname('account_setting')
            ->data('permission', ['role list','permission list'])
            ->link->attr(["class" => ""])
            ->href('#account_setting');

            $menu->account_setting->add('<span>'.__('message.list_form_title',['form' => __('message.role')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'role.index'])
                ->data('permission', 'role list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->account_setting->add('<span>'.__('message.list_form_title',['form' => __('message.permission')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'permission.index'])
                ->data('permission', 'permission list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.additionalfees').'</span>', [ 'class' => ''])
            ->prepend('<i class="fas fa-file-invoice-dollar"></i>')
            ->nickname('additionalfees')
            ->data('permission', 'additionalfees list')
            ->link->attr(['class' => ''])
            ->href('#additionalfees');

            $menu->additionalfees->add('<span>'.__('message.list_form_title',['form' => __('message.additionalfees')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'additionalfees.index'])
                ->data('permission', 'additionalfees list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->additionalfees->add('<span>'.__('message.add_form_title',['form' => __('message.additionalfees')]).'</span>', ['class' => request()->is('additionalfees/*/edit') ? 'sidebar-layout active' : 'sidebar-layout','route' => 'additionalfees.create'])
                ->data('permission', [ 'additionalfees add', 'additionalfees edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);
        
        $menu->add('<span>'.__('message.sos').'</span>', [ 'class' => ''])
            ->prepend('<i class="fas fa-address-book"></i>')
            ->nickname('sos')
            ->data('permission', 'sos list')
            ->link->attr(['class' => ''])
            ->href('#sos');

            $menu->sos->add('<span>'.__('message.list_form_title',['form' => __('message.sos')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'sos.index'])
                ->data('permission', 'sos list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);

            $menu->sos->add('<span>'.__('message.add_form_title',['form' => __('message.sos')]).'</span>', ['class' => request()->is('sos/*/edit') ? 'sidebar-layout active' : 'sidebar-layout','route' => 'sos.create'])
                ->data('permission', [ 'sos add', 'sos edit'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.pushnotification').'</span>', [ 'class' => '' ])
            ->prepend('<i class="fas fa-bullhorn"></i>')
            ->nickname('pushnotification')
            ->data('permission', 'pushnotification list')
            ->link->attr(['class' => ''])
            ->href('#pushnotification');

            $menu->pushnotification->add('<span>'.__('message.list_form_title',['form' => __('message.pushnotification')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'pushnotification.index'])
                ->data('permission', 'pushnotification list')
                ->prepend('<i class="fas fa-list"></i>')
                ->link->attr(['class' => '']);
            
            $menu->pushnotification->add('<span>'.__('message.add_form_title',['form' => __('message.pushnotification')]).'</span>', ['class' => 'sidebar-layout', 'route' => 'pushnotification.create'])
                ->data('permission', [ 'pushnotification add'])
                ->prepend('<i class="fas fa-plus-square"></i>')
                ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.report',['name' => '']).'</span>', ['class' => ''])
            ->prepend('<i class="far fa-copy"></i>')
            ->nickname('report')
            ->data('permission', '')
            ->link->attr(['class' => ''])
            ->href('#report');

            $menu->report->add('<span>'.__('message.report',['name' => __('message.admin')]).'</span>', ['class' => 'sidebar-layout' ,'route' => 'adminEarningReport'])
                    ->data('permission', '')
                    ->prepend('<i class="fas fa-file-contract"></i>')
                    ->link->attr(['class' => '']);

            $menu->report->add('<span>'.__('message.driver_earning').'</span>', ['class' => ( request()->is('driver-earning') || request()->is('driver-earning/*') ) ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driver.earning.report'])
                ->data('permission', ['driverearning list'])
                ->prepend('<i class="fas fa-money-bill"></i>')
                ->link->attr(['class' => '']);

            $menu->report->add('<span>'.__('message.service_wise').'</span>', ['class' => ( request()->is('service-wise') || request()->is('service-wise/*') ) ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'serviceWiseReport'])
                ->data('permission', ['service-wise-report'])
                ->prepend('<i class="fas fa-money-bill"></i>')
                ->link->attr(['class' => '']);

            //$menu->report->add('<span>'.__('message.report',['name' => __('message.driver')]).'</span>', ['class' => ( request()->is('driver-report-list') || request()->is('driver-report-list/*') ) ? 'sidebar-layout active' : 'sidebar-layout', 'route' => 'driver.report.list'])
            //    ->data('permission', ['driverearning list'])
            //    ->prepend('<i class="fas fa-file-contract"></i>')
            //    ->link->attr(['class' => '']);

        $menu->add('<span>'.__('message.pages').'</span>', ['class' => ''])
                ->prepend('<i class="fas fa-file"></i>')
                ->nickname('pages')
                ->data('permission', 'pages')
                ->link->attr(['class' => ''])
                ->href('#pages');
                
                $menu->pages->add('<span>'.__('message.list').'</span>', ['class' => 'sidebar-layout' ,'route' => 'pages.index'])
                    ->data('permission', 'page List')
                    ->prepend('<i class="fas fa-file-contract"></i>')
                    ->link->attr(['class' => '']);

                $menu->pages->add('<span>'.__('message.terms_condition').'</span>', ['class' => 'sidebar-layout' ,'route' => 'term-condition'])
                    ->data('permission', 'terms condition')
                    ->prepend('<i class="fas fa-file-contract"></i>')
                    ->link->attr(['class' => '']);
                
                $menu->pages->add('<span>'.__('message.privacy_policy').'</span>', ['class' => 'sidebar-layout' ,'route' => 'privacy-policy'])
                    ->data('permission', 'privacy policy')
                    ->prepend('<i class="fas fa-user-shield"></i>')
                    ->link->attr(['class' => '']);       
        
        $menu->add('<span>'.__('message.driver_location').'</span>', ['route' => 'map'])
                ->prepend('<i class="fas fa-map"></i>')
                ->nickname('map')
                ->data('permission', 'driver location');
        
        $menu->add('<span>'.__('message.setting').'</span>', ['route' => 'setting.index'])
                ->prepend('<i class="fas fa-cogs"></i>')
                ->nickname('setting')
                ->data('permission', 'system setting');

         $menu->add('<span>'.__('message.website_section').'</span>', ['class' => ''])
            ->prepend('<i class="fas fa-globe-asia"></i>')
            ->nickname('website_section')
            ->data('permission', 'website_section list')
            ->link->attr(['class' => ''])
            ->href('#website_section');

            $menu->website_section->add('<span>'. __('message.information').'</span>', ['class' => 'sidebar-layout' ,'route' => [ 'frontend.website.form', 'app_info'] ])
                ->data('permission', 'information list')
                ->prepend('<i class="fas fa-file-alt"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section->add('<span>'. __('message.our_mission').'</span>', ['class' => 'sidebar-layout' ,'route' => 'our-mission.index'])
                ->data('permission', 'our_mission list')
                ->prepend('<i class="fa fa-star"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section->add('<span>'. __('message.why_choose').'</span>', ['class' => 'sidebar-layout' ,'route' => 'why-choose.index'])
                ->data('permission', 'why_choose list')
                ->prepend('<i class="fa fa-handshake"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section->add('<span>'. __('message.client_testimonials').'</span>', ['class' => 'sidebar-layout' ,'route' => 'client-testimonials.index'])
                ->data('permission', 'client_testimonials list')
                ->prepend('<i class="fas fa-thumbs-up"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section->add('<span>'. __('message.downloandapp').'</span>', ['class' => 'sidebar-layout', 'route' => [ 'frontend.website.form', 'download_app'] ])
                ->data('permission', 'downloandapp list')
                ->prepend('<i class="fas fa-download"></i>')
                ->link->attr(['class' => '']);

            $menu->website_section->add('<span>'. __('message.contactinfo').'</span>', ['class' => 'sidebar-layout', 'route' => [ 'frontend.website.form', 'contactus_info'] ])
                ->data('permission', 'contactinfo list')
                ->prepend('<i class="fas fa-id-badge"></i>')
                ->link->attr(['class' => '']);

        })->filter(function ($item) {
            return checkMenuRoleAndPermission($item);
        });
@endphp

<div class="mm-sidebar sidebar-default">
    <div class="mm-sidebar-logo d-flex align-items-center justify-content-between">
        <a href="{{ route('home') }}" class="header-logo">
            <img src="{{ getSingleMedia(appSettingData('get'),'site_logo',null) }}" class="img-fluid mode light-img rounded-normal light-logo site_logo_preview" alt="logo">
            <img src="{{ getSingleMedia(appSettingData('get'),'site_dark_logo',null) }}" class="img-fluid mode dark-img rounded-normal darkmode-logo site_dark_logo_preview" alt="dark-logo">
        </a>
        <div class="side-menu-bt-sidebar">
            <i class="fas fa-bars wrapper-menu"></i>
        </div>
    </div>

    <div class="data-scrollbar" data-scroll="1">
        <nav class="mm-sidebar-menu">
            <ul id="mm-sidebar-toggle" class="side-menu">
                @include(config('laravel-menu.views.bootstrap-items'), ['items' => $MyNavBar->roots()])       
            </ul>
        </nav>
        <div class="pt-5 pb-5"></div>
    </div>
</div>
