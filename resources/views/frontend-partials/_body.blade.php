{{-- <div id="loading">
    @include('partials._body_loader')
</div> --}}
@include('frontend-partials._body_header')


<div id="remoteModelData" class="modal fade" role="dialog"></div>
<div class="content-page">
    {{ $slot }}
</div>

@include('frontend-partials._body_footer')

@include('frontend-partials._scripts')
