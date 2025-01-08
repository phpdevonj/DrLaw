<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-end">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">{{ __('message.filter') }}</h5>
                <a href="javascript:void();" data-bs-dismiss="modal" aria-label="Close"><i class="ri-close-circle-fill" style="font-size: 25px"></i></a>
            </div>
            <div class="modal-body">
                {{ Form::open(['method' => 'GET', 'id' => 'riderequest-filter-form']) }}
                    <div class="row mb-3">
                        <div class="col-lg-6">
                            {{ Form::label('start_date', __('message.start_date'), [ 'class' => 'form-control-label']) }}
                            {{ Form::text('start_date', old('start_date'),[ 'placeholder' => __('message.start_date'),'class' => 'form-control min-datepickerall']) }}
                        </div>
    
                        <div class="col-lg-6">
                            {{ Form::label('end_date', __('message.end_date'), [ 'class' => 'form-control-label']) }}
                            {{ Form::text('end_date', old('end_date'),[ 'placeholder' => __('message.end_date'),'class' => 'form-control min-datepickerall']) }}
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        {{ Form::label('rider_id', __('message.rider'), ['class' => 'form-label']) }}
                        {{ Form::select('rider_id',[], request('rider_id') , [
                                'data-ajax--url' => route('ajax-list', [ 'type' => 'rider' ]),
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.rider') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    <div class="form-group mb-3">
                        {{ Form::label('driver_id', __('message.driver'), ['class' => 'form-label']) }}
                        {{ Form::select('driver_id',[], request('driver_id') , [
                                'data-ajax--url' => route('ajax-list', [ 'type' => 'driver' ]),
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.driver') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    <div class="form-group mb-3">
                        {{ Form::label('payment_status', __('message.payment_status_message'), ['class' => 'form-label']) }}
                        {{ Form::select('payment_status',[ '' => '','paid' => __('message.paid') ,'pending' => __('message.pending') ,'failed' => __('message.failed') ], request('payment_status') , [
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.payment_status_message') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    <div class="form-group mb-3">
                        {{ Form::label('payment_method', __('message.payment_method'), ['class' => 'form-label']) }}
                        {{ Form::select('payment_method',[ '' => '','cash' => __('message.cash') ,'wallet' => __('message.wallet')], request('payment_method') , [
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.payment_method') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    <div class="form-group mb-3">
                        {{ Form::label('ride_status', __('message.status'), ['class' => 'form-label']) }}
                        {{ Form::select('ride_status',['' => ''] + rideStatus(), request('payment_status') , [
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.status') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
            </div>
                <div class="modal-footer">
                    <button id="reset-filter-btn" type="button" class="btn btn-warning border-radius-10 text-dark me-2 text-decoration-none">
                        {{ __('message.reset_filter') }}
                    </button>
                    <button type="submit" class="btn btn-primary">{{ __('message.apply_filter') }}</button>
                </div>
            {{ Form::close() }}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $(document).ready(function() {
        $('#riderequest-filter-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route('riderequest.index') }}?' + formData).load();
        });

        $('#reset-filter-btn').on('click', function() {
            $('#start_date').val('').trigger('change');
            $('#end_date').val('').trigger('change');
            $('#rider_id').val('').trigger('change');
            $('#driver_id').val('').trigger('change');
            $('#payment_status').val('').trigger('change');
            $('#payment_method').val('').trigger('change');
            $('#ride_status').val('').trigger('change');
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route('riderequest.index') }}').load();
        });
    });
</script>