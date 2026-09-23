{{ Form::open(['method' => 'POST','route' => ['referralSettingsUpdate'],'data-toggle'=>'validator']) }}
{{ Form::hidden('page', $page, ['class' => 'form-control'] ) }}
    
    <div class="col-md-12 mt-20">
        <div class="row">
            @foreach($referral_setting as $key => $value)
                {{ Form::hidden('type[]', $key , ['class' => 'form-control'] ) }}
                <div class="col-md-6 form-group">
                    @if($key == "referral_bonus_points")
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }}
                    @elseif($key == "referral_reward_condition")
                        {{ Form::label('referral_reward_condition',__('message.referral_reward_condition'), ['class' => 'col-sm-12  form-control-label']) }}
                        {{ Form::select($key,[ 'on_registration' => __('message.on_registration') ,'on_first_ride_complete' => __('message.on_first_ride_complete') ], isset($value) ? $value : 'on_registration' ,[ 'class' =>'form-control select2js','required']) }}
                    @elseif($key == "unique_device_id")
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        @php
                            $value = isset($value) ? $value : 1;
                        @endphp
                        <div class="d-block">
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('unique_device_id', '1' , $value == 1 ? true : '' , ['class' => 'custom-control-input', 'id' => 'unique_device_id_yes' ]) }}
                                {{ Form::label('unique_device_id_yes', __('message.yes'), ['class' => 'custom-control-label' ]) }}
                            </div>
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('unique_device_id', '0' , $value == 0 ? true : '', ['class' => 'custom-control-input', 'id' => 'unique_device_id_no' ]) }}
                                {{ Form::label('unique_device_id_no', __('message.no'), ['class' => 'custom-control-label' ]) }}
                            </div>
                        </div>
                    @elseif($key == "referral_max_limit")
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }}
                    @endif
                </div>
            @endforeach
        </div>
    </div>
{{ Form::submit(__('message.save'), ['class'=>"btn btn-md btn-primary float-md-right"]) }}
{{ Form::close() }}
