{{ Form::open(['method' => 'GET', 'id' => 'admin_report_filter_form' ]) }}
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-end">
            <div class="modal-content h-100">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('message.filter') }}</h5>
                    <a href="javascript:void();" data-bs-dismiss="modal" aria-label="Close"><i class="ri-close-circle-fill" style="font-size: 25px"></i></a>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            {{ Form::label('from_date', __('message.from'), ['class' => 'form-label']) }}
                            {{ Form::date('from_date', $params['from_date'] ?? request('from_date'), ['class' => 'form-control', 'id' => 'from_date_main']) }}
                        </div>
                        <div class="col-md-6">
                            {{ Form::label('to_date', __('message.to'), ['class' => 'form-label']) }}
                            {{ Form::date('to_date', $params['to_date'] ?? request('to_date'), ['class' => 'form-control', 'id' => 'to_date_main']) }}
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-md-12 mt-1">
                            {{ Form::label('driver_id',__('message.driver'),['class'=>'form-control-label'],false) }}
                            {{ Form::select('driver_id', [] , request('driver_id') , [
                                    'data-ajax--url' => route('ajax-list', [ 'type' => 'driver' ]), 
                                    'class' =>'form-control select2',
                                    'data-placeholder' => __('message.select_field', [ 'name' => __('message.driver') ]),
                                    'data-allow-clear' => 'true'
                                ] )
                            }}
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button id="reset-filter-btn" type="button" class="btn btn-warning border-radius-10 text-dark me-2 text-decoration-none">
                        {{ __('message.reset_filter') }}
                    </button>
                    <button type="submit" class="btn btn-dark border-radius-10">{{ __('message.apply_filter') }}</button>
                </div>
            </div>
        </div>
    </div>
{{ Form::close() }}