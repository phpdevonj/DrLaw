<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ $pageTitle ?? ''}}</h4>
                        </div>
                        
                        <div class="d-flex">
                            <div class="me-2">
                                {!! $button !!}
                            </div>
                            @if (isset($rideRequestfilterButton) && $rideRequestfilterButton == true || isset($complaintfilterButton) && $complaintfilterButton == true || isset($driverDocumentFilterButton) && $driverDocumentFilterButton == true || isset($defaultKeywordFilterButton) && $defaultKeywordFilterButton == true)    
                                <button id="filterToggle" class="float-right btn btn-md border-radius-10 btn-warning ml-2" type="button" data-bs-toggle="modal" data-bs-target="#filterModal">
                                    <i class="fas fa-filter"></i> {{ __('message.filter') }}
                                </button>                                                                                                           
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if (isset($rideRequestfilterButton) && $rideRequestfilterButton == true)
        @include('riderequest.filter')                                                                                                          
    @endif

    @if (isset($complaintfilterButton) && $complaintfilterButton == true)
        @include('complaint.filter')                                                                                                          
    @endif

    @if (isset($driverDocumentFilterButton) && $driverDocumentFilterButton == true)
        @include('driver_document.filter')                                                                                                          
    @endif
    
    {{--  @if (isset($defaultKeywordFilterButton) && $defaultKeywordFilterButton == true)
        @include('app-language-setting.defaultkeyword.filter')                                                                                                          
    @endif
      --}}
    @section('bottom_script')
       {{ $dataTable->scripts() }}
       <script>
            $('.select2').select2({
                dropdownParent: $('#filterModal'),
            });
       </script>
    @endsection
</x-master-layout>
