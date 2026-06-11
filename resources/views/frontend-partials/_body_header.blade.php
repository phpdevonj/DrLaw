<nav class="navbar main-nav">
    <div class="container nav-container">
        <a class="navbar-brand" href="{{ route('browse') }}">
            <img src="{{ getSingleMediaSettingImage(getSettingFirstData('app_info','logo_image'),'logo_image') }}"
                class="nav-img" height="40" width="40">
            <span class="text-white ms-2 nav-text">{{ SettingData('app_info', 'app_name') }}</span>
        </a>
    </div>
</nav>
