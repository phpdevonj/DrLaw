<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['pushnotification.update', $id], 'method' => 'patch', 'enctype' => 'multipart/form-data' ]) !!}
        @else
            {!! Form::open(['route' => ['pushnotification.store'], 'method' => 'post', 'enctype' => 'multipart/form-data' ]) !!}
        @endif
        <div class="row">
            <div class="col-12">
                <a href="{{route('sos.index')}}" class="btn border-radius-10 btn-dark float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
            </div>
            <div class="col-lg-12 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('rider', __('message.rider').' <span class="text-danger">*</span>',['class' => 'form-control-label' ], false ) }}
                                    {{ Form::select('rider[]', $rider , old('rider') , [ 'data-placeholder' => __('message.select_name',[ 'select' => __('message.rider') ]), 'id' => 'rider_list', 'class' => 'select2js form-control', 'multiple' => 'multiple'] ) }}
                                </div>

                                <div class="form-group col-md-2">
                                    <div class="custom-control custom-checkbox mt-4 pt-3">
                                        <input type="checkbox" class="custom-control-input selectAll" id="all_rider" data-usertype="rider">
                                        <label class="custom-control-label" for="all_rider">{{ __('message.selectall') }}</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('driver', __('message.driver').' <span class="text-danger">*</span>',['class' => 'form-control-label' ], false ) }}
                                    {{ Form::select('driver[]', $driver , old('driver') , [ 'data-placeholder' => __('message.select_name',[ 'select' => __('message.driver') ]), 'id' => 'driver_list', 'class' => 'select2js form-control', 'multiple' => 'multiple'] ) }}
                                </div>

                                <div class="form-group col-md-2">
                                    <div class="custom-control custom-checkbox mt-4 pt-3">
                                        <input type="checkbox" class="custom-control-input selectAll" id="all_driver" data-usertype="driver">
                                        <label class="custom-control-label" for="all_driver">{{ __('message.selectall') }}</label>
                                    </div>
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('title', __('message.title').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::text('title', old('title'),[ 'placeholder' => __('message.title'),'class' =>'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-12">
                                    {{ Form::label('message',__('message.message').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::textarea('message', null, [ 'class' => 'form-control textarea', 'rows' => 3, 'required', 'placeholder' => __('message.message') ]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-control-label" for="image">{{ __('message.image') }}</label>
                                    <div class="custom-file">
								        {{ Form::file('notification_image', [ 'class'=> 'custom-file-input', 'id' => 'notification_image', 'data--target' => 'notification_image_preview', 'lang' => 'en', 'accept'=> 'image/*' ]) }}
                                        <label class="custom-file-label">{{  __('message.choose_file',['file' =>  __('message.image') ]) }}</label>
                                    </div>
                                    <span class="selected_file"></span>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <img id="notification_image_preview" src="{{ asset('images/default.png') }}" alt="image" class="attachment-image mt-2 pt-1 notification_image_preview border-radius-10 w-50">
                                </div>
                            </div>
                            <hr>
                            {{ Form::button('<span id="button-loader" style="display:none;"><div class="spinner-border spinner-border-sm text-light" role="status"></div></span> ' . __('message.save'), [
                                'type' => 'submit',
                                'class' => 'btn border-radius-10 btn-success float-right',
                                'id' => 'submit-btn'
                            ]) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
    @section('bottom_script')
    <script>
        $(document).ready(function() {
            $(document).on('click', '.selectAll', function() {
                var usertype = $(this).attr('data-usertype');
                var userDropdown = $('#' + usertype + '_list');

                if ($(this).is(':checked')) {
                    userDropdown.find('option').prop('selected', true);
                    userDropdown.trigger('change');
                    updateCounter(usertype);
                } else {
                    userDropdown.val(null).trigger('change');
                    updateCounter(usertype);
                }
            });
        
            function updateCounter(usertype) {
                $('#' + usertype + '_list').next('span.select2').find('ul').html(function() {
                    let count = $('#' + usertype + '_list').select2('data').length;
                    return "<li class='ml-2'>" + count + " " + usertype.charAt(0).toUpperCase() + usertype.slice(1) + " Selected</li>";
                });
            }
        });
    </script>
    @endsection
</x-master-layout>
