{{ Form::open(['method' => 'GET', 'id' => 'default_keyword_filter_form']) }}
    <div class="row align-items-end">
        <div class="form-group col-md-3">
            {{ Form::label('screen', __('message.select_name', ['select' => __('message.screen')]), ['class' => 'form-control-label']) }}
            {{ Form::select('screen', isset($screen) ? [$screen->screenId => $screen->screenName] : [], old('screen'), [
                'class' => 'select2Clear form-control screen',
                'data-placeholder' => __('message.select_name', ['select' => __('message.screen')]),
                'data-ajax--url' => route('ajax-list', ['type' => 'screen']),
            ]) }}
        </div>

        <div class="form-group col-md-3">
            {{ Form::label('keyword_title', __('message.keyword_title'), ['class' => 'form-label']) }}
            {{ Form::select('keyword_title', [], request('keyword_title'), [
                'data-ajax--url' => route('ajax-list', ['type' => 'defaultkeyword']),
                'class' => 'form-control select2js',
                'data-placeholder' => __('message.select_field', ['name' => __('message.keyword_title')]),
                'data-allow-clear' => 'true'
            ]) }}
        </div>

        <div class="form-group col-md-4 d-flex justify-content-start align-items-end">
            <button type="submit" class="btn btn-success border-radius-10 text-white me-3 mr-1">
                {{ __('message.apply_filter') }}
            </button>
            <button id="reset-filter-btn" type="button" class="btn btn-warning border-radius-10 text-dark">
                {{ __('message.reset_filter') }}
            </button>
        </div>
    </div>
{{ Form::close() }}
