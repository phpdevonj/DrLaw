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
                                @if(isset($pdfbutton))
                                {!! $pdfbutton !!}
                                @endif
                                @if(isset($import_file_button))
                                    {!! $import_file_button !!}
                                @endif
                            </div>
                            <button id="filterToggle" class="float-right btn btn-md border-radius-10 btn-warning ml-2" type="button" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="fas fa-filter"></i> {{ __('message.filter') }}
                            </button>                                                                                                           
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-header-toolbar">
                            @if(isset($delete_checkbox_checkout))
                               {!! $delete_checkbox_checkout !!}
                            @endif
                        </div>
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('app-language-setting.languagewithkeyword.filter')

    @section('bottom_script')
       {{ $dataTable->scripts() }}
        <script>
            $(document).ready(function() {
                $(document).find('.select2').select2({
                    width: '100%',
                    allowClear: true,
                    dropdownParent: $('#filterModal'),
                });
            });
        </script>
    @endsection
</x-master-layout>
