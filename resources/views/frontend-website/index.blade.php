<x-frontend-layout :assets="$assets ?? []">

    <div class="bg-img-section">
        <div class="content">
            <h1 class="text-white bg-text">{{ $data['app_info']['image_title'] }}</h1>
            <div class="app-section">
                <a href="{{ $data['download_app']['play_store']['url'] }}" {{ $data['download_app']['play_store']['target'] }} class="text-decoration-none">
                    <img src="{{ asset('frontend-website/img/play_store.png') }}" alt="Play Store" class="web-icon-img mb-1">
                </a>

                <a href="{{ $data['download_app']['app_store']['url'] }}" {{ $data['download_app']['app_store']['target'] }}>
                    <img src="{{ asset('frontend-website/img/app_store.png') }}" alt="App Store" class="web-icon-img mb-1">
                </a>
            </div>
        </div>

        <img src="{{ $data['app_info']['backgound_image'] }}" class="bg-image">

        <img src="{{ asset('frontend-website/img/divider_image.png') }}" class="divider-img" alt="Divider">
    </div>

    <div class="container our-mission">
        <div class="row">
            <div class="col-lg-6 d-flex align-items-center">
                <img src="{{ $data['our_mission']['our_mission_image'] }}" class="our-mission-img">
            </div>
            <div class="col-lg-6">
                <h1 class="our-mission-h1 text-gradient-border mt-5">{{ $data['our_mission']['title'] }}</h1>

                <p class="our-mission-p">
                    @foreach ($our_mission as $ourmission)
                        <p>{{ $ourmission->description }}</p>
                    @endforeach
                </p>

            </div>
        </div>
    </div>

    <div class="why-choose-section">
        <div class="content-overlay">
                <img src="{{ $data['why_choose']['why_choose_image'] }}" class="whychoose_image">
            <div class="why-choose-content">
                <div class="container-fluid">
                    <h2 class="text-white">{{ $data['why_choose']['title'] }}</h2>
                    <h6 class="text-white">{{ $data['why_choose']['subtitle'] }}
                    </h6>
                    <div class="row gy-5">
                        @foreach ($why_choose as $key => $why_choose_data)
                            <div class="col-md-4">
                                <div class="card why-choose-card h-100">
                                    <div class="card-body why-choose-card-body" id="card{{ $key }}">
                                        <div class="d-flex justify-content-center">
                                            <div class="why-round-image">
                                                <img src="{{ getSingleMediaSettingImage($why_choose_data->id != null ? $why_choose_data : null ,'frontend_data_image','whychoose_image') }}" height="60" width="60">
                                            </div>
                                        </div>
                                        <h5 class="card-title text-white text-center mt-4 mb-2 why-h5">
                                            {{ $why_choose_data->title }}
                                        </h5>
                                        <p class="card-text text-center mt-2 mb-2 why-p">
                                            {{ $why_choose_data->description }}
                                        </p>
                                        <p class="text-white text-center mt-3 mb-1 read-more"
                                            data-id="card{{ $key }}">{{ __('message.read_more') }}
                                        </p>
                                        <p class="text-white text-center mt-3 mb-1 read-less"
                                            data-id="card{{ $key }}" style="display: none;">
                                            {{ __('message.read_less') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="testimonialCarousel" class="carousel slide testimonial-container" data-bs-ride="carousel"
        data-bs-interval="3000">
        <img src="{{ asset('frontend-website/img/divider_image.png') }}" class="divider-img" alt="Divider">

        <div class="container">
            <div class="carousel-inner">
                <div class="testimonial-content">
                    <h2 class="text-gradient-border">{{ $data['client_testimonials']['title'] }}</h2>
                    <p>{{ $data['client_testimonials']['subtitle'] }}</p>
                </div>
                @foreach ($items as $key => $item)
                    <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                        <div class="testimonial p-4 d-flex align-items-center">
                            <div class="col-lg-6 testimonial-item">
                                <h4 class="text-white marbot-30">
                                    {{ $item->title }}
                                </h4>
                                <p class="text-white marbot-30 testimonial-p">
                                    {{ $item->description }}
                                </p>
                                <h5>
                                    - {{$item->subtitle }}
                                </h5>
                            </div>
                            <div class="col-lg-6 d-flex justify-content-end position-relative">
                                <img src="{{ asset('frontend-website/img/profile_border.png') }}"  class="profile-border-img testimonial-image-div">
                                <div class="profile-image-wrapper testimonial-image-div">
                                    <img src="{{ getSingleMediaSettingImage($item->id != null ? $item : null,'frontend_data_image','testimonial_image') }}" class="profile-image testimonial-image-div">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row download-section">
            <div class="col-md-6 mobile-section">
                <div>
                    <h2 class="text-gradient-border download-now">{{ $data['download_app']['title'] }}</h2>
                    <p>{{ $data['download_app']['subtitle'] }}</p>
                    <div class="app-section">
                        <a href="{{ $data['download_app']['play_store']['url'] }}" {{ $data['download_app']['play_store']['target'] }} class="text-decoration-none">
                            <img src="{{ asset('frontend-website/img/play_store.png') }}" alt="Play Store" class="web-icon-img mb-1">
                        </a>
                        <a href="{{ $data['download_app']['app_store']['url'] }}" {{ $data['download_app']['app_store']['target'] }}>
                            <img src="{{ asset('frontend-website/img/app_store.png') }}" alt="App Store" class="web-icon-img mb-1">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 d-flex align-items-start">
                <div class="mobile_image">
                    <img src="{{ $data['download_app']['download_app_image'] }}" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script>
           $(document).ready(function() {
            $('.why-choose-card-body').each(function() {
                var cardBody = $(this);
                var cardText = cardBody.find('.why-p');
                var readMore = cardBody.find('.read-more');
                var readLess = cardBody.find('.read-less');

                if (cardText[0].scrollHeight > 100) {
                    readMore.show();
                } else {
                    readMore.hide();
                    readLess.hide();
                }
            });

                $('.read-more').click(function() {
                    var cardId = $(this).data('id');
                    var cardBody = $('#' + cardId);

                    if (!cardBody.hasClass('expanded')) {
                        cardBody.addClass('expanded').removeClass('collapsed');
                        $(this).hide();
                        cardBody.find('.read-less').show();

                        adjustImageHeight();
                    }
                });

                $('.read-less').click(function() {
                    var cardId = $(this).data('id');
                    var cardBody = $('#' + cardId);

                    if (!cardBody.hasClass('collapsed')) {
                        cardBody.addClass('collapsed').removeClass('expanded');
                        $(this).hide();
                        cardBody.find('.read-more').show();

                        adjustImageHeight();
                    }
                });

                $('.why-choose-card-body').addClass('collapsed');

                function adjustImageHeight() {
                    const image = document.querySelector('.whychoose_image');
                    const content = document.querySelector('.why-choose-content');

                    image.style.height = 'auto';

                    const newContentHeight = content.offsetHeight;

                    if (newContentHeight > image.offsetHeight) {
                        image.style.height = newContentHeight + 'px';
                    }
                }

                adjustImageHeight();
            });
        </script>
    @endsection

</x-frontend-layout>
