<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ $pageTitle ?? ''}}</h4>
                        </div>
                        {{ Form::open(['method' => 'GET']) }}
                            <div class="row justify-content-end align-items-end">
                                <div class="form-group col-auto">
                                    {{ Form::label('from_date',__('message.from').'<span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                    {{ Form::date('from_date',$params['from_date'] ?? request('from_date'),[ 'placeholder' => __('message.date'),'class' =>'form-control min-datepickerall', 'id' => 'from_date_main']) }}
                                </div>
                                <div class="form-group col-auto">
                                    {{ Form::label('to_date',__('message.to').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                    {{ Form::date('to_date', $params['to_date'] ?? request('to_date'),[ 'placeholder' => __('message.date'), 'class' =>'form-control min-datepickerall', 'id' => 'to_date_main']) }}
                                </div>
                                <div class="form-group col-sm-0 mr-3">
                                    <button type="submit" class="btn btn-md btn-primary text-white">{{ __('message.apply_filter') }}</button>
                                    <a href="{{ route('driver.earning.report') }}" class="btn btn-md btn-light text-dark"><i class="ri-repeat-line" style="font-size:12px"></i> {{ __('message.reset_filter') }}</a>
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-success btn-md text-center dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ __('message.export') }}
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                            <a class="dropdown-item" href="{{ route('download-driver-earning',request()->all()) }}">
                                                <i class="fas fa-file-csv"></i> {{__('message.excel')}}
                                            </a>
                                            <a class="dropdown-item" href="{{ route('download-driverearningpdf',request()->all()) }}">
                                                <i class="fas fa-file-pdf"></i> {{__('message.pdf')}}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        {{ Form::close() }}
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
       {{ $dataTable->scripts() }}
    @endsection
</x-master-layout>
