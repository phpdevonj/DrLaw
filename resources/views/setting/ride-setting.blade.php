{{ Form::open(['method' => 'POST','route' => ['rideSettingsUpdate'],'data-toggle'=>'validator']) }}
{{ Form::hidden('page', $page, ['class' => 'form-control'] ) }}
    <div class="col-md-12 mt-20">
        <div class="row">
            @foreach($ride_setting as $key => $value)
                <div class="col-md-6 form-group">
                    @if($key == 'preset_tip_amount' )
                        {{ Form::label($key,__('message.'.$key).' <span data-toggle="tooltip" data-placement="right" title="'.__('message.preset_tip_amount_info').'"><i class="fas fa-question-circle"></i></span>',['class'=>'form-control-label'],false ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => '0|5|10|50', 'class' => 'form-control' ]) }}
                    @elseif( $key == 'surge_price' )
                        {{ Form::label($key, __('message.'.$key), ['class' => 'form-control-label']) }}
                        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline mt-2">
                            <div class="custom-switch-inner">
                                {{ Form::hidden($key, 0) }}
                                {{ Form::checkbox($key, 1, $value == '1' , [
                                    'class' => 'custom-control-input bg-dark',
                                    'data-type' => 'pages',
                                    'data-id' => $key,
                                    'id' => 'switch_'.$key
                                ]) }} 
                                <label class="custom-control-label ml-2" for="switch_{{ $key }}"></label>
                            </div>
                        </div>           
                    <!-- @elseif( $key == 'is_bidding' )
                        {{ Form::label($key, __('message.'.$key), ['class' => 'form-control-label']) }}
                        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline mt-2">
                            <div class="custom-switch-inner">
                                {{ Form::hidden($key, 0) }}
                                {{ Form::checkbox($key, 1, $value == '1' , [
                                    'class' => 'custom-control-input bg-dark',
                                    'data-type' => 'pages',
                                    'data-id' => $key,
                                    'id' => 'switch_'.$key
                                ]) }} 
                                <label class="custom-control-label ml-2" for="switch_{{ $key }}"></label>
                            </div>
                        </div>   -->
                    @elseif( $key == 'apply_additional_fee' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        @php
                            $value = isset($value) ? $value : 1;
                        @endphp
                        <div class="d-block">
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('apply_additional_fee', '1' , $value == 1 ? true : '' , ['class' => 'custom-control-input', 'id' => 'apply_additional_fee_yes' ]) }}
                                {{ Form::label('apply_additional_fee_yes', __('message.yes'), ['class' => 'custom-control-label' ]) }}
                            </div>
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('apply_additional_fee', '0' , $value == 0 ? true : '', ['class' => 'custom-control-input', 'id' => 'apply_additional_fee_no' ]) }}
                                {{ Form::label('apply_additional_fee_no', __('message.no'), ['class' => 'custom-control-label' ]) }}
                            </div>
                        </div>
                    @elseif( $key == 'loyalty_program' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        @php
                            $value = isset($value) ? $value : 0;
                        @endphp
                        <div class="d-block">
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('loyalty_program', '1' , $value == 1 ? true : '' , ['class' => 'custom-control-input', 'id' => 'loyalty_program_yes' ]) }}
                                {{ Form::label('loyalty_program_yes', __('message.yes'), ['class' => 'custom-control-label' ]) }}
                            </div>
                            <div class="custom-control custom-radio custom-control-inline col-2">
                                {{ Form::radio('loyalty_program', '0' , $value == 0 ? true : '', ['class' => 'custom-control-input', 'id' => 'loyalty_program_no' ]) }}
                                {{ Form::label('loyalty_program_no', __('message.no'), ['class' => 'custom-control-label' ]) }}
                            </div>
                        </div>
                    @elseif( $key == 'point_ratio' )
                        {{ Form::label($key,__('message.'.$key).' <span data-toggle="tooltip" data-placement="right" title="'.__('message.point_ratio_info').'"><i class="fas fa-question-circle"></i></span>',['class'=>'form-control-label'],false ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => 'Point ratio', 'class' => 'form-control' ]) }}
                    @elseif( $key == 'point_value' )
                        {{ Form::label($key,__('message.'.$key).' <span data-toggle="tooltip" data-placement="right" title="'.__('message.point_value_info').'"><i class="fas fa-question-circle"></i></span>',['class'=>'form-control-label'],false ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => 'Point value', 'class' => 'form-control' ]) }}
                    @elseif( $key == 'min_points_withdrawal' )
                        {{ Form::label($key,__('message.'.$key).' <span data-toggle="tooltip" data-placement="right" title="'.__('message.min_points_withdrawal_info').'"><i class="fas fa-question-circle"></i></span>',['class'=>'form-control-label'],false ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => 'Minimum Points for Withdrawal', 'class' => 'form-control' ]) }}
                    @elseif( $key == 'rider_rating' )
                        {{ Form::label($key, __('message.'.$key), ['class' => 'form-control-label']) }}
                        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline mt-2">
                            <div class="custom-switch-inner">
                                {{ Form::hidden($key, 0) }}
                                {{ Form::checkbox($key, 1, $value == '1' , [
                                    'class' => 'custom-control-input bg-dark',
                                    'data-type' => 'pages',
                                    'data-id' => $key,
                                    'id' => 'switch_'.$key
                                ]) }} 
                                <label class="custom-control-label ml-2" for="switch_{{ $key }}"></label>
                            </div>
                        </div>
                    @elseif( $key == 'driver_rating' )
                        {{ Form::label($key, __('message.'.$key), ['class' => 'form-control-label']) }}
                        <div class="custom-switch custom-switch-text custom-switch-color custom-control-inline mt-2">
                            <div class="custom-switch-inner">
                                {{ Form::hidden($key, 0) }}
                                {{ Form::checkbox($key, 1, $value == '1' , [
                                    'class' => 'custom-control-input bg-dark',
                                    'data-type' => 'pages',
                                    'data-id' => $key,
                                    'id' => 'switch_'.$key
                                ]) }} 
                                <label class="custom-control-label ml-2" for="switch_{{ $key }}"></label>
                            </div>
                        </div>
                    @elseif( $key == 'normal_ride_restriction_buffer' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }}
                    @elseif( $key == 'scheduled_ride_restriction_buffer' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }}
                    <!-- @elseif( $key == 'ride_accept_button_distance' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }} -->
                    @elseif( $key == 'completed_before_destination_distance' )
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::text($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'class' => 'form-control' ]) }}
                    @else
                        {{ Form::label($key,__('message.'.$key),['class'=>'form-control-label'] ) }}
                        {{ Form::number($key,$value ?? null,[ 'placeholder' => __('message.'.$key), 'min' => 0, 'step' => 'any', 'class' => 'form-control' ]) }}
                    @endif
                </div>
            @endforeach
        </div>
    </div>
{{ Form::submit(__('message.save'), ['class'=>"btn btn-md btn-primary float-md-right"]) }}
{{ Form::close() }}
