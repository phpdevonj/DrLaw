<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-end">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">{{ __('message.filter') }}</h5>
                <a href="javascript:void();" data-bs-dismiss="modal" aria-label="Close"><i class="ri-close-circle-fill" style="font-size: 25px"></i></a>
            </div>
            <div class="modal-body">
                {{ Form::open(['method' => 'GET', 'id' => 'lang_keyword_filter_form']) }}
                    <div class="form-group">
                        {{ Form::label('language', __('message.select_name',[ 'select' => __('message.language') ]), [ 'class' => 'form-control-label' ]) }}
                        {{ Form::select('language', isset($language) ? [ $language->id  => $language->language_name ] : [], old('language'), [
                            'class' => 'select2 form-group language',
                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.language') ]),
                            'data-ajax--url' => route('ajax-list', [ 'type' => 'languagetable' ]),
                        ]) }}
                    </div>
                    <div class="form-group">
                        {{ Form::label('keyword', __('message.select_name',[ 'select' => __('message.keyword') ]), [ 'class' => 'form-control-label' ]) }}
                        {{ Form::select('keyword', isset($keyword) ? [ $keyword->id  => $keyword->keyword_name ] : [], old('keyword'), [
                            'class' => 'select2 form-group keyword',
                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.keyword') ]),
                            'data-ajax--url' => route('ajax-list', [ 'type' => 'defaultkeyword' ]),
                        ]) }}
                    </div>
                    <div class="form-group">
                        {{ Form::label('screen', __('message.select_name',[ 'select' => __('message.screen') ]), [ 'class' => 'form-control-label' ]) }}
                        {{ Form::select('screen', isset($screen) ? [ $screen->screenId  => $screen->screenName ] : [], old('screen'), [
                            'class' => 'select2 form-group screen',
                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.screen') ]),
                            'data-ajax--url' => route('ajax-list', [ 'type' => 'screen' ]),
                        ]) }}
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
        $('#lang_keyword_filter_form').on('submit', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("languagewithkeyword.index") }}?' + formData).load();
        });

        $('#reset-filter-btn').on('click', function() {
            $('#language').val('').trigger('change');
            $('#keyword').val('').trigger('change');
            $('#screen').val('').trigger('change');
            var table = $('#dataTableBuilder').DataTable();
            table.ajax.url('{{ route("languagewithkeyword.index") }}').load();
        });
    });
</script>