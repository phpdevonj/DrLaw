@php
    $app_settings =  App\Models\AppSetting::first();
    $dummy_data = Dummydata('dummy_title');
    $pages = App\Models\Pages::where('status', '1')->get();
@endphp

<footer class="footer mt-auto py-3" style="background-image: url({{ getSingleMediaSettingImage(getSettingFirstData('contactus_info','image'),'image','contactus_info')  }});">
    <div class="container-fluid footer-container">
        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-6">
                <h5 class="text-white footer-heading">{{ __('message.about') }}</h5>
                <p class="footer-content footer-link"> {{ SettingData('contactus_info', 'about_title') ?? $dummy_data }}</p>
                <ul class="info-share d-inline list-inline">
                    <li class="list-inline-item">
                        <a class="text-white text-decoration-none" href="{{ $app_settings->twitter_url ?? 'javascript:void(0)' }}" {{ $app_settings->twitter_url != null ? 'target="_blank"' : '' }} >
                            <i class="fab fa-twitter"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a class="text-white text-decoration-none" href="{{ $app_settings->facebook_url ?? 'javascript:void(0)' }}" {{ $app_settings->facebook_url != null ? 'target="_blank"' : '' }}>
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a class="text-white text-decoration-none" href="{{ $app_settings->instagram_url ?? 'javascript:void(0)' }}" {{ $app_settings->instagram_url != null ? 'target="_blank"' : '' }}>
                            <i class="fab fa-instagram"></i>
                        </a>
                    </li>

                    <li class="list-inline-item">
                        <a class="text-white text-decoration-none footer-p" href="{{ $app_settings->linkedin_url ?? 'javascript:void(0)' }}" {{ $app_settings->linkedin_url != null ? 'target="_blank"' : '' }}>
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </li>

                </ul>
            </div>
            <div class="col-lg-2 col-md-6 col-sm-6 mt-4 mt-lg-0 mt-md-0">
                <h5 class="text-white footer-heading">{{ __('message.links') }}</h5>
                <ul class="list-unstyled">
                    <li><a class="footer-content footer-link hover-stop"
                        href="{{ $app_settings->help_support_url ?? 'javascript:void(0)' }}"
                        {{ $app_settings->help_support_url != null ? 'target="_blank"' : '' }}>
                        {{ __('message.help_and_support') }}
                        </a> 
                    </li>
                    <li><a href="{{ route('privacypolicy') }}" class="footer-content footer-link hover-stop">{{ __('message.privacy_policy') }}</a></li>
                    <li><a href="{{ route('termofservice') }}" class="footer-content footer-link hover-stop">{{ __('message.terms_conditions') }}</a></li>
                </ul>
            </div>

            @if (count($pages) > 0)           
                <div class="col-lg-2 col-md-6 col-sm-6 mt-4 mt-lg-0 mt-md-0">
                    <h5 class="text-white footer-heading">{{ __('message.pages') }}</h5>
                    <ul class="list-unstyled">
                        @foreach ($pages as $page)
                        <li class="mb-3">
                            <a href="{{ isset($page->slug) && $page->slug != null ? route('pages', ['slug' => $page->slug]) : 'javascript:void(0)' }}" class="footer-pages-content text-white text-decoration-none">
                                {{ ucwords($page->title) }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="col-lg-4 col-md-6 col-sm-6 mt-lg-0 mt-4">
                <h5 class="text-white footer-heading">{{ __('message.contact') }}</h5>
                <p class="footer-content d-flex align-items-center">
                    <a href="{{ $app_settings->contact_email ? 'mailto:' . $app_settings->contact_email : 'javascript:void(0)' }}"
                        {{ $app_settings->contact_email ? 'target="_blank"' : '' }}
                        class="text-white text-decoration-none"> <i class="fa fa-envelope text-white fa-lg me-2"></i>
                        {{ $app_settings->contact_email ?? $dummy_data }}
                    </a>
                </p>
                <p class="footer-content d-flex align-items-center">
                    <a href="{{ $app_settings->contact_number ? 'tel:' . $app_settings->contact_number : 'javascript:void(0)' }}"
                        {{ $app_settings->contact_number ? 'target="_blank"' : '' }}
                        class="text-white text-decoration-none">
                        <i class="fa fa-phone me-2 fa-lg phone-icon"></i>
                        {{ $app_settings->contact_number ?? $dummy_data }}
                    </a>
                </p>
            </div>
        </div>

        <div class="divider"></div>

        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <img src="{{ getSingleMediaSettingImage(getSettingFirstData('app_info','logo_image'),'logo_image') }}" class="img-fluid me-3" height="35" width="35">
                <span class="text-white logo-name">{{ SettingData('app_info', 'app_name') }}</span>
            </div>
            <div>
                <a href="{{ route('termofservice') }}" class="footer-content terms-condition me-2 hover-stop">{{ __('message.terms_of_use') }}</a>
                <a href="{{ route('privacypolicy') }}" class="footer-content terms-condition hover-stop">{{ __('message.privacy_policy') }}</a>
            </div>
        </div>

    </div>
    <div class="text-center footer-content footer-bottom copyright">
        {{ $app_settings->site_copyright ?? $dummy_data }}
    </div>
</footer>
