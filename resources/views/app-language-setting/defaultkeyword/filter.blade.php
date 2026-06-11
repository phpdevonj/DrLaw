<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-end">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">{{ __('message.filter') }}</h5>
                <a href="javascript:void();" data-bs-dismiss="modal" aria-label="Close"><i class="ri-close-circle-fill" style="font-size: 25px"></i></a>
            </div>
            <div class="modal-body">
                {{ Form::open(['method' => 'GET', 'id' => 'default_keyword_filter_form']) }}
                    <div class="form-group mb-3">
                        {{ Form::label('keyword_title', __('message.keyword_title'), ['class' => 'form-label']) }}
                        {{ Form::select('keyword_title',[], request('keyword_title') , [
                                'data-ajax--url' => route('ajax-list', [ 'type' => 'defaultkeyword' ]),
                                'class' =>'form-control select2js',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.keyword_title') ]),
                                'data-allow-clear' => 'true'
                            ])
                        }}
                    </div>
                    <div class="form-group mb-3">
                        {{ Form::label('screen_id', __('message.screen_id'), ['class' => 'form-label']) }}
                        {{ Form::select('screen_id',[], request('screen_id') , [
                                'data-ajax--url' => route('ajax-list', [ 'type' => 'screen' ]),
                                'class' =>'form-control select2js',
                                'data-placeholder' => __('message.select_field', [ 'name' => __('message.screen_id') ]),
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
        $('#default_keyword_filter_form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("defaultkeyword.index") }}?' + formData).load();
        });

        $('#reset-filter-btn').on('click', function() {
            $('#keyword_title').val('').trigger('change');
            $('#screen_id').val('').trigger('change');
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("defaultkeyword.index") }}').load();
        });
    });
</script>