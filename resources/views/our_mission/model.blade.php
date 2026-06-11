<?php $id = $id ?? null; ?>
@if (isset($id))
    {{ Form::model($data, ['route' => ['our-mission.update', $id], 'method' => 'patch' ]) }}
@else
    {{ Form::open(['route' => ['our-mission.store'], 'method' => 'post']) }}
@endif
<div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">{{ __($pageTitle) }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group col-md-12">
                {{ Form::label('description', __('message.description'). ' <span class="text-danger">*</span>',['class' => 'form-control-label'],false) }}
                {{ Form::textarea('description',old('description'), ['class' => 'form-control textaraea', 'rows' => 2, 'placeholder' => __('message.description')]) }}
            </div>
        </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">{{ __('message.close') }}</button>
                <button type="submit" class="btn btn-md btn-primary" id="btn_submit" data-form="ajax">{{ isset($id) ? __('message.update') : __('message.save') }}</button>
            </div>
        </div>
    </div>
{{ Form::close() }}

