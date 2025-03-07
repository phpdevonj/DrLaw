<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        {!! Form::model($data, ['route' => ['useraddress.update', $id], 'method' => 'patch' ]) !!}
        <div class="row">
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
                                {{ Form::hidden('user_id', $data->user_id) }}
                                <div class="form-group col-md-4">
                                    {{ Form::label('address_type', __('message.address_type').' <span class="text-danger">*</span>',[ 'class' => 'form-control-label' ], false) }}
                                    {{ Form::select('address_type', [ 'home' => __('message.home'), 'work' => __('message.work') , 'other' => __('message.other'), 'custom' => __('message.custom') ], $data->label, [ 'class' => 'form-control select2js', 'id' => 'address_type', 'required']) }}
                                </div>

                                <div class="form-group col-md-4" id="custom_label_container" style="display: none;">
                                    {{ Form::label('custom_label', __('message.custom_label').' <span class="text-danger">*</span>' ,['class' => 'form-control-label'], false) }}
                                    {{ Form::text('custom_label',$data->custom_label,[ 'id' => 'custom_label', 'placeholder' => __('message.custom_label'),'class' =>'form-control']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('street_address', __('message.street_address').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false) }}
                                    {{ Form::text('street_address', $data->address_line1,[ 'id' => 'street_address', 'placeholder' => __('message.street_address'),'class' =>'form-control', 'required']) }}
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
    <script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_KEY')}}&libraries=places" defer></script>
    <script>
        $(function() {

            $(document).ready(function() {
                $('#street_address').val('');
            });

            if(window.google || window.google.maps) {
                initialize();
            }
            function initialize() {
                var street_address_input = document.getElementById('street_address');
                var street_address = new google.maps.places.Autocomplete(street_address_input);

                street_address.addListener('place_changed', function () {
                    var place = street_address.getPlace();
                    if (!place.geometry) {
                        alert("{{ __('message.address_autocomplete_error', ['address' => __('message.street_address')]) }}");
                        $('#street_address').focus();
                        return;
                    }
                    start_latitude  = place.geometry['location'].lat();
                    start_longitude = place.geometry['location'].lng();
                    $('#start_latitude').val(start_latitude);
                    $('#start_longitude').val(start_longitude);
                    $('#street_address').val(place.formatted_address);
                    serviceList(start_latitude, start_longitude);                        
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Function to toggle custom label field
            function toggleCustomLabel() {
                var addressType = $('#address_type').val();
                var customLabelContainer = $('#custom_label_container');
                var customLabelInput = $('#custom_label');
                
                if (addressType === 'custom') {
                    customLabelContainer.show();
                    customLabelInput.prop('required', true);
                } else {
                    customLabelContainer.hide();
                    customLabelInput.prop('required', false);
                    customLabelInput.val(''); // Clear the value when hidden
                }
            }

            // Initial check on page load
            toggleCustomLabel();

            // Listen for changes on address type select
            $('#address_type').on('change', function() {
                toggleCustomLabel();
            });
        });
    </script>
    @endsection
</x-master-layout>
