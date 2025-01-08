<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-end">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">{{ __('message.filter') }}</h5>
                <a href="javascript:void();" data-bs-dismiss="modal" aria-label="Close"><i class="ri-close-circle-fill" style="font-size: 25px"></i></a>
            </div>
            <div class="modal-body">
                {{ Form::open(['method' => 'GET', 'id' => 'complaint-filter-form']) }}
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
                        {{ Form::label('complaint_by', __('message.complaint_by'), ['class' => 'form-label']) }}
                        {{ Form::select('complaint_by',[ '' => '','rider' => __('message.rider') ,'driver' => __('message.driver')], request('complaint_by') , [
                                'class' =>'form-control select2',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.complaint_by') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    
                    <div class="form-group mb-3">
                        {{ Form::label('status', __('message.status'), ['class' => 'form-label']) }}
                        {{ Form::select('status',['' => '','pending' => __('message.pending'), 'investigation' => __('message.investigation'), 'resolved' => __('message.resolved')], request('status') , [
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
        $('#complaint-filter-form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("complaint.index") }}?' + formData).load();
        });

        $('#reset-filter-btn').on('click', function() {
            $('#rider_id').val('').trigger('change');
            $('#driver_id').val('').trigger('change');
            $('#complaint_by').val('').trigger('change');
            $('#status').val('').trigger('change');
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("complaint.index") }}').load();
        });
    });
</script>