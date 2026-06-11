<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ $pageTitle ?? ''}}</h4>
                        </div>
                        <div class="card-body">
                            {{ Form::open(['method' => 'GET']) }}
                                <div class="row justify-content-end align-items-end">
                                    <div class="form-group col-auto">
                                        {{ Form::label('driver', __('message.driver'), ['class' => 'form-control-label']) }}
                                        {{ Form::select('driver', isset($user_data) ? [ $user_data->id => $user_data->display_name ] : [] , old('driver') , [
                                            'data-ajax--url' => route('ajax-list', [ 'type' => 'driver' ]),
                                            'data-placeholder' => __('message.select_field', [ 'name' => __('message.driver') ]),
                                            'class' =>'form-control select2js', 'required',
                                            'id' => 'driver'
                                            ])
                                        }}
                                    </div>
                                    <div class="form-group col-auto">
                                        {{ Form::label('from_date',__('message.from').'<span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                        {{ Form::date('from_date',$params['from_date'] ?? request('from_date'),[ 'placeholder' => __('message.date'),'class' =>'form-control min-datepickerall', 'id' => 'from_date_main']) }}
                                    </div>
                                    <div class="form-group col-auto">
                                        {{ Form::label('to_date',__('message.to').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                        {{ Form::date('to_date', $params['to_date'] ?? request('to_date'),[ 'placeholder' => __('message.date'), 'class' =>'form-control min-datepickerall', 'id' => 'to_date_main']) }}
                                    </div>
                                    <div class="form-group col-sm-0 mr-3">
                                        <button type="submit" class="btn btn-md btn-primary text-white  clearListPropertynumber">{{ __('message.apply_filter') }}</button>
                                        <a href="{{ route('driver.report.list') }}" class="btn btn-md btn-light text-dark">
                                            <i class="ri-repeat-line" style="font-size:12px"></i> {{ __('message.reset_filter') }}
                                        </a>
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-success btn-md text-center dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                {{ __('message.export') }}
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="{{ route('download.driver.report',request()->all()) }}">
                                                    <i class="fas fa-file-csv"></i> {{__('message.excel')}}
                                                </a>
                                                <a class="dropdown-item" href="{{ route('download.driver.report.pdf',request()->all()) }}">
                                                    <i class="fas fa-file-pdf"></i> {{__('message.pdf')}}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {{ Form::close() }}
                        </div>
                    </div>
                    <div class="card-body">
                        <table id="basic-table" class="table mb-1 border-none  text-center" role="grid">
                            <thead>
                                <tr>
                                    <th scope='col'>{{ __('message.rider_id') }}</th>
                                    <th scope='col'>{{ __('message.title_name',['title' => __('message.driver')]) }}</th>
                                    <th scope='col'>{{ __('message.total_amount') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.driver_earning') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.admin_commission') }}</th>
                                    <th scope='col'>{{ __('message.created_at') }}</th>
                                    <th scope='col'>{{ __('message.status') }}</th>
                                </tr>
                            </thead>
                            @if(count($data) > 0)
                                <tbody>
                                    @foreach ($data as $values)
                                        <tr>
                                            <td><a href="{{ route('rider.show', $values->rider_id) }}">{{ $values->rider_id }}</a></td>
                                            <td><a href="{{ route('driver.show', $values->driver_id) }}">{{ optional($values->driver)->display_name ?? '-' }}</a></td>
                                            <td>{{ getPriceFormat(optional($values->payment)->total_amount) ?? '-' }}</td>
                                            <td>{{ getPriceFormat(optional($values->payment)->driver_commission) ?? '-' }}</td>
                                            <td>{{ getPriceFormat(optional($values->payment)->admin_commission) ?? '-' }}</td>
                                            <td>{{ dateAgoFormate($values->created_at, true) }}</td>
                                            <td><span class=" badge bg-primary">{{ __('message.'.$values->status) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="font-weight-bold text-left">{{ __('message.total_amount') }}</td>
                                        <td class="text-center font-weight-bold">{{ getPriceFormat($data->sum('payment.total_amount')) }}</td>
                                        <td class="text-center font-weight-bold">{{ getPriceFormat($data->sum('payment.driver_commission')) }}</td>
                                        <td class="text-center font-weight-bold">{{ getPriceFormat($data->sum('payment.admin_commission')) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tbody>
                            @else
                                <tbody>
                                    <tr>
                                        <td colspan="7">{{ __('message.no_record_found') }}</td>
                                    </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
        <script>
            $('.select2').select2({
                dropdownParent: $('#filterModal'),
            });
            $("#basic-table").DataTable({
                searching: false,
                "dom":  '<"row align-items-center"<"col-md-2"><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"d-flex" <"flex-grow-1" l><"p-2" i><"mt-4" p>><"clear">',
                language: {
                    search: '',
                    searchPlaceholder: "{{ __('pagination.search') }}",
                    lengthMenu : "{{  __('pagination.show'). ' _MENU_ ' .__('pagination.entries')}}",
                    zeroRecords: "{{__('pagination.no_records_found')}}",
                    info: "{{__('pagination.showing') .' _START_ '.__('pagination.to') .' _END_ ' . __('pagination.of').' _TOTAL_ ' . __('pagination.entries')}}", 
                    infoFiltered: "{{__('pagination.filtered_from_total') . ' _MAX_ ' . __('pagination.entries')}}",
                    infoEmpty: "{{__('pagination.showing_entries')}}",
                    paginate: {
                        previous: "{{__('pagination.__previous')}}",
                        next: "{{__('pagination.__next')}}"
                    }
                },
                "order": [[0, "desc"]]
            });
            $(document).on('click', '.paginate_button', function() {
                pagination_btn_style_check = '{{ $params["datatable_botton_style"] }}';
                if ( pagination_btn_style_check ) {
                    $("<style>")
                        .prop("type", "text/css").html("\
                            .dataTables_paginate {\
                                display: block !important;\
                                opacity: 1 !important;\
                            }\
                        ")
                        .appendTo("head");
                }
            });
        </script>
    @endsection
    </x-master-layout>

