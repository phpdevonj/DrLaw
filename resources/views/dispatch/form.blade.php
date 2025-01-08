<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['dispatch.update', $id], 'method' => 'patch', 'data-toggle'=>'validator' ]) !!}
        @else
            {!! Form::open(['route' => ['dispatch.store'], 'method' => 'post', 'data-toggle'=>'validator' ]) !!}
        @endif
        <div class="row">
            <div class="col-12">
                <?php echo $button; ?>
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
                                {{ Form::hidden('start_latitude', null, [ 'id' => 'start_latitude'] ) }}
                                {{ Form::hidden('start_longitude', null, [ 'id' => 'start_longitude']) }}
                                {{ Form::hidden('end_latitude', null, [ 'id' => 'end_latitude'] ) }}
                                {{ Form::hidden('end_longitude', null, [ 'id' => 'end_longitude']) }}
                                <div class="form-group col-md-4">
                                    {{ Form::label('rider_id', __('message.rider').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::select('rider_id', isset($id) ? [ optional($data->rider)->id => optional($data->rider)->display_name ] : [] , old('rider_id') , [
                                            'data-ajax--url' => route('ajax-list', [ 'type' => 'rider' ]),
                                            'class' =>'form-control select2js',
                                            'data-placeholder' => __('message.select_field', [ 'name' => __('message.rider') ]),
                                            'required'
                                        ])
                                    }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('start_address', __('message.start_address').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false) }}
                                    {{ Form::text('start_address', old('start_address'),[ 'placeholder' => __('message.start_address'),'class' =>'form-control', 'required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('end_address', __('message.end_address').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false) }}
                                    {{ Form::text('end_address', old('end_address'),[ 'placeholder' => __('message.end_address'),'class' =>'form-control', 'required']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('service_id', __('message.service').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    <a class="float-right serviceList" href="javascript:void(0)"><i class="ri-refresh-line"></i></a>
                                    {{ Form::select('service_id', isset($id) ? [ optional($data->service)->id => optional($data->service)->display_name ] : [] , old('service_id') , [
                                            'class' => 'select2js form-group service',
                                            'required',
                                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.service') ]),
                                        ])
                                    }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('driver_id', __('message.driver'), ['class' => 'form-control-label'], false) }}
                                    <a class="float-right driverList" href="#"><i class="ri-refresh-line"></i></a>
                                    {{ Form::select('driver_id', isset($id) ? [ optional($data->driver)->id => optional($data->driver)->display_name ] : [] , old('driver_id') , [
                                            'class' => 'select2js form-group driver',
                                            'data-placeholder' => __('message.select_name',[ 'select' => __('message.driver') ]),
                                        ])
                                    }}
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex">
                                <h5>{{ __('message.drop_address') }}</h5>
                                <button type="button" id="add_button" class="btn btn-sm btn-secondary float-right ml-2 mb-2">{{ __('message.add') }}</button>
                            </div>
                            
                            <div id="dropAddress" class="clone-master">
                                <div class="row clone-item" id="row_0" row="0" data-id="0">
                                    <div class="form-group col-md-6">
                                        <input type="textbox" name="search_drop_location[]" value="" class="form-control drop_location" id='search_drop_location_0' row="0" placeholder="{{ __('message.drop_address') }}"  />
                                        <input type="hidden" name="drop_location[]" value="" class="form-control hidden_drop_location" id='drop_location_0' row="0" />
                                    </div>
                                    <a href="javascript:void(0)" id="remove_0" class="removebtn text-danger mt-2 p-1" row="0">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                            <hr>
                            {{ Form::button('<span id="button-loader" style="display:none;"><div class="spinner-border spinner-border-sm text-light" role="status"></div></span> ' . __('message.book_now'), [
                                'type' => 'submit',
                                'class' => 'btn btn-success border-radius-10 float-right',
                                'id' => ''
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
            $(function() {

                $(document).ready(function() {
                    $('#start_address').val('');
                    $('#end_address').val('');
                    $(".drop_location input[type='text']").val('');
                    let drop_location = [];
                    resetSequenceNumbers();
                });

                var resetSequenceNumbers = function() {
                    $("#dropAddress .clone-item").each(function(i) {
                        // $(this).find('td:first').text(i + 1);
                        placeholder = "{{ __('message.drop_address') }}";
                        $(this).find('input.drop_location').attr('placeholder', placeholder +' '+ parseInt( i + 1 ))
                    });
                };

                $("#add_button").click(function() {
                    let cloneMaster = $(document).find(".clone-master");
                    
                    if (cloneMaster.length > 0) {
                        let cloneItems = cloneMaster.find('.clone-item:first');
                        if (cloneItems.length > 0) {
                            let newClone = cloneItems.clone();

                            newClone.find('.removebtn').show().fadeIn(300);
                            newClone.find('input').val('');
                            
                            let lastCount = parseInt(cloneMaster.find('.clone-item:last').attr('row'));
                            
                            let newCloneCount = lastCount + 1;

                            newClone.attr('id', 'row_'+ newCloneCount).attr('row', newCloneCount);
                            
                            newClone.find('input.drop_location').attr('id', 'search_drop_location_'+ newCloneCount ).attr('row', newCloneCount); 
                            newClone.find('input.hidden_drop_location').attr('id', 'drop_location_'+ newCloneCount ).attr('row', newCloneCount);
                            newClone.find('[id^="remove_"]').attr('id',"remove_"+newCloneCount).attr('row',newCloneCount);
                            cloneMaster.append(newClone);

                            search_drop_location = document.getElementById('search_drop_location_'+ newCloneCount)
                            autocomplete1 = new google.maps.places.Autocomplete(search_drop_location);

                            autocomplete1.addListener('place_changed', function() {
                            curretrow = newCloneCount;
                            var place = autocomplete1.getPlace();
                            
                            if (!place.geometry) {
                                alert("{{ __('message.address_autocomplete_error', ['address' => __('message.drop_address')]) }}");
                                
                                $('#search_drop_location_'+curretrow).focus();
                                return;
                            }

                            place_data = {
                                'latitude'  : place.geometry['location'].lat().toString(),
                                'longitude' : place.geometry['location'].lng().toString(),
                                'address'   : place.formatted_address,
                                'distance'  : 0,
                                'postion'   : curretrow,
                            };
                            // drop_location.push(place_data)
                            $('#drop_location_'+curretrow).val(JSON.stringify(place_data));
                        });
                            
                        }
                        resetSequenceNumbers();
                    }
                });

                $(document).on('click','.removebtn', function()
                {
                    var row = $(this).attr('row');
                    var delete_row  = $('#row_'+row);

                    var total_row = $('#dropAddress .clone-item').length;
                    
                    var user_response = confirm("{{ __('message.delete_msg') }}");
                    if(!user_response) {
                        return false;
                    }

                    if(total_row == 1){
                        $(document).find('#add_button').trigger('click');
                    }
                    delete_row.remove();
                    resetSequenceNumbers();
                });

                if(window.google || window.google.maps) {
                    initialize();
                }
                function initialize() {
                    var start_address_input = document.getElementById('start_address');
                    var start_address = new google.maps.places.Autocomplete(start_address_input);

                    start_address.addListener('place_changed', function () {
                        var place = start_address.getPlace();
                        if (!place.geometry) {
                            alert("{{ __('message.address_autocomplete_error', ['address' => __('message.start_address')]) }}");
                            $('#start_address').focus();
                            return;
                        }
                        start_latitude  = place.geometry['location'].lat();
                        start_longitude = place.geometry['location'].lng();
                        $('#start_latitude').val(start_latitude);
                        $('#start_longitude').val(start_longitude);
                        $('#start_address').val(place.formatted_address);
                        serviceList(start_latitude, start_longitude);                        
                    });

                    var end_address_input = document.getElementById('end_address');
                    var end_address = new google.maps.places.Autocomplete(end_address_input);

                    end_address.addListener('place_changed', function () {
                        var endplace = end_address.getPlace();
                        if (!endplace.geometry) {
                            alert("{{ __('message.address_autocomplete_error', ['address' => __('message.end_address')]) }}");
                            $('#end_address').focus();
                            return;
                        }
                        end_latitude  = endplace.geometry['location'].lat();
                        end_longitude = endplace.geometry['location'].lng();
                        $('#end_latitude').val(end_latitude);
                        $('#end_longitude').val(end_longitude);
                        $('#end_address').val(endplace.formatted_address);                        
                    });                   

                    $('.drop_location').each(function() {
                        var curretrow = $(this).attr('row');
                        var inputField = document.getElementById('search_drop_location_'+curretrow);
                        
                        var autocomplete = new google.maps.places.Autocomplete(inputField);

                        autocomplete.addListener('place_changed', function() {
                            var place = autocomplete.getPlace();
                            
                            if (!place.geometry) {
                                alert("{{ __('message.address_autocomplete_error', ['address' => __('message.drop_address')]) }}");
                                
                                $('#search_drop_location_'+curretrow).focus();
                                return;
                            }

                            place_data = {
                                'latitude'  : place.geometry['location'].lat().toString(),
                                'longitude' : place.geometry['location'].lng().toString(),
                                'address'   : place.formatted_address,
                                'distance'  : 0,
                                'postion'   : curretrow,
                            };
                            // drop_location.push(place_data)
                            $('#drop_location_'+curretrow).val(JSON.stringify(place_data));
                        });
                    });
                }

                $(document).on('change', '.drop_location', function () {
                    var curretrow = $(this).attr('row');
                    
                    var inputField = document.getElementById('search_drop_location_'+curretrow);
                    
                    autocomplete = new google.maps.places.Autocomplete(inputField);

                    autocomplete.addListener('place_changed', function() {
                        var place = autocomplete.getPlace();
                        
                        if (!place.geometry) {
                            alert("{{ __('message.address_autocomplete_error', ['address' => __('message.drop_address')]) }}");
                            
                            $('#search_drop_location_'+curretrow).focus();
                            return;
                        }

                        place_data = {
                            'latitude'  : place.geometry['location'].lat().toString(),
                            'longitude' : place.geometry['location'].lng().toString(),
                            'address'   : place.formatted_address,
                            'distance'  : 0,
                            'postion'   : curretrow,
                        };
                        // drop_location.push(place_data)
                        $('#drop_location_'+curretrow).val(JSON.stringify(place_data));
                    });
                });

                $(document).on('change', '.service', function ()
                {
                    var service_id = $(this).val();
                    $('.driver').empty();
                    if( service_id != null ) {
                        driverList(service_id);
                    }
                })
            });

            $('.serviceList').on('click',function() {
                start_latitude = $('#start_latitude').val();
                start_longitude = $('#start_longitude').val();

                if( start_latitude != '' && start_longitude != '' ) {
                    serviceList(start_latitude,start_longitude);
                } else {
                    $('.service').empty();
                }
            });

            function serviceList(latitude, longitude) {
                var route = "{{ route('ajax-list',[ 'type' => 'service_for_ride']) }}&latitude="+latitude+"&longitude="+longitude;
                route = route.replaceAll('amp;','');
                
                $.ajax({
                    url: route,
                    success: function(result){
                        $('.service').select2({
                            width : '100%',
                            placeholder: "{{ __('message.select_name',[ 'select' => __('message.service') ]) }}",
                            data: result.results
                        });

                        $(".service").val(latitude).trigger('change');
                    }
                })
            }

            $('.driverList').on('click',function() {
                service_id = $('#service_id').val();
                if( service_id != null ) {
                    driverList(service_id);
                } else {
                    $('.driver').empty();
                }
            });
            
            function driverList(service_id)
            {
                latitude = $('#start_latitude').val();
                longitude = $('#start_longitude').val();

                var route = "{{ route('ajax-list',[ 'type' => 'driver_for_ride']) }}&service_id="+service_id+"&latitude="+latitude+"&longitude="+longitude;
                route = route.replaceAll('amp;','');
                
                $.ajax({
                    url: route,
                    success: function(result){
                        $('.driver').select2({
                            width : '100%',
                            placeholder: "{{ __('message.select_name',[ 'select' => __('message.driver') ]) }}",
                            data: result.results
                        });

                        $(".driver").val(service_id).trigger('change');
                    }
                })
            }
        </script>
    @endsection
</x-master-layout>
