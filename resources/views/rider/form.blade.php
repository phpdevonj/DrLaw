<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['rider.update', $id], 'method' => 'patch' , 'enctype' => 'multipart/form-data','id' => 'rider_form']) !!}
        @else
            {!! Form::open(['route' => ['rider.store'], 'method' => 'post', 'enctype' => 'multipart/form-data','id' => 'rider_form']) !!}
        @endif
        <div class="row">
            <div class="col-12">
                <a href="{{route('rider.index')}}" class="btn border-radius-10 btn-dark float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
            </div>
            <div class="col-xl-3 col-lg-4 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between"  style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="crm-profile-img-edit position-relative text-center">
                                <img src="{{ $profileImage ?? asset('images/user/1.jpg')}}" alt="User-Profile" class="crm-profile-pic rounded-circle avatar-100">
                                <div class="crm-p-image bg-primary">
                                    <svg class="upload-button" width="14" height="14" viewBox="0 0 24 24">
                                        <path fill="#ffffff" d="M14.06,9L15,9.94L5.92,19H5V18.08L14.06,9M17.66,3C17.41,3 17.15,3.1 16.96,3.29L15.13,5.12L18.88,8.87L20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18.17,3.09 17.92,3 17.66,3M14.06,6.19L3,17.25V21H6.75L17.81,9.94L14.06,6.19Z" />
                                    </svg>
                                    <input class="file-upload" type="file" accept="image/*" name="profile_image">
                                </div>
                            </div>
                            <div class="img-extension mt-3">
                                <div class="d-inline-block align-items-center">
                                    <span>{{ __('message.only') }}</span>
                                    @foreach(config('constant.IMAGE_EXTENTIONS') as $extention)
                                        <a href="javascript:void();">.{{ $extention }}</a>
                                    @endforeach
                                    <span>{{ __('message.allowed') }}</span>
                                </div>
                                <hr>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('message.status') }}</label>
                            <div class="row" style="--bs-gap: 1rem;">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        {{ Form::radio('status', 'active', old('status') === 'active', ['class' => 'form-check-input', 'id' => 'status-active']) }}
                                        {{ Form::label('status-active', __('message.active'), ['class' => 'form-check-label']) }}
                                    </div>
                                    <div class="form-check">
                                        {{ Form::radio('status', 'inactive', old('status') === 'inactive', ['class' => 'form-check-input', 'id' => 'status-inactive']) }}
                                        {{ Form::label('status-inactive', __('message.inactive'), ['class' => 'form-check-label']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        {{ Form::radio('status', 'pending', old('status') === 'pending', ['class' => 'form-check-input', 'id' => 'status-pending']) }}
                                        {{ Form::label('status-pending', __('message.pending'), ['class' => 'form-check-label']) }}
                                    </div>
                                    <div class="form-check">
                                        {{ Form::radio('status', 'banned', old('status') === 'banned', ['class' => 'form-check-input', 'id' => 'status-banned']) }}
                                        {{ Form::label('status-banned', __('message.banned'), ['class' => 'form-check-label']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-9 col-lg-8 mt-3">
                <div class="card border-radius-20">
                    <div class="card-header d-flex justify-content-between" style="border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }} {{ __('message.information') }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ Form::label('first_name',__('message.first_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('first_name',old('first_name'),['placeholder' => __('message.first_name'),'class' =>'form-control','required']) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('last_name',__('message.last_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('last_name',old('last_name'),['placeholder' => __('message.last_name'),'class' =>'form-control','required']) }}
                                </div>
                                
                                <div class="form-group col-md-6">
                                    {{ Form::label('email',__('message.email').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::email('email', old('email'), [ 'placeholder' => __('message.email'), 'class' => 'form-control', 'required' ]) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('username',__('message.username').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('username', old('username'), ['class' => 'form-control', 'required', 'placeholder' => __('message.username') ]) }}
                                </div>

                                @if(!isset($id))
                                    <div class="form-group col-md-6">
                                        {{ Form::label('password',__('message.password').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                        {{ Form::password('password', ['class' => 'form-control', 'placeholder' =>  __('message.password') ]) }}
                                    </div>
                                @endif

                                <div class="form-group col-md-6">
                                    {{ Form::label('contact_number',__('message.contact_number').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::text('contact_number', old('contact_number'),[ 'placeholder' => __('message.contact_number'), 'class' => 'form-control', 'id' => 'phone' ]) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ Form::label('gender',__('message.gender').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                    {{ Form::select('gender',[ 'male' => __('message.male') ,'female' => __('message.female') , 'other' => __('message.other') ], old('gender') ,[ 'class' =>'form-control select2js','required']) }}
                                </div>

                                @if(isset($id))
                                    <div class="form-group col-md-6">
                                        {{ Form::label('bank_name',__('message.bank_name'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[bank_name]', old('userBankAccount[bank_name]'), ['class' => 'form-control', 'placeholder' => __('message.bank_name')]) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('bank_code',__('message.bank_code'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[bank_code]', old('userBankAccount[bank_code]'), ['class' => 'form-control', 'placeholder' => __('message.bank_code')]) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('account_holder_nameaccount_holder_name',__('message.account_holder_name'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[account_holder_name]', old('userBankAccount[account_holder_name]'), ['class' => 'form-control', 'placeholder' => __('message.account_holder_name')]) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('account_number',__('message.account_number'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[account_number]', old('userBankAccount[account_number]'), ['class' => 'form-control', 'placeholder' => __('message.account_number')]) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('routing_number',__('message.routing_number'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[routing_number]', old('userBankAccount[routing_number]'), ['class' => 'form-control', 'placeholder' => __('message.routing_number')]) }}
                                    </div>
                                    
                                    <div class="form-group col-md-6">
                                        {{ Form::label('bank_iban',__('message.bank_iban'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[bank_iban]', old('userBankAccount[bank_iban]'), ['class' => 'form-control', 'placeholder' => __('message.bank_iban')]) }}
                                    </div>

                                    <div class="form-group col-md-6">
                                        {{ Form::label('bank_swift',__('message.bank_swift'),['class'=>'form-control-label'], false ) }}
                                        {{ Form::text('userBankAccount[bank_swift]', old('userBankAccount[bank_swift]'), ['class' => 'form-control', 'placeholder' => __('message.bank_swift')]) }}
                                    </div>
                                @endif

                                <div class="form-group col-md-6">
                                    {{ Form::label('address',__('message.address'), ['class' => 'form-control-label']) }}
                                    {{ Form::textarea('address', null, ['class'=>"form-control textarea" , 'rows'=>3  , 'placeholder'=> __('message.address') ]) }}
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
</x-master-layout>

@section('bottom_script')
    <script>
        $(document).ready(function(){
            formValidation("#rider_form", {
                first_name: { required: true },
                last_name: { required: true },
                email: { required: true, email: true },
                username: { required: true },
                password: { required: true },
                contact_number: { required: true },
                gender: { required: true },
            }, {
                first_name: { required: "{{__('message.please_enter_first_name')}}" },
                last_name: { required: "{{__('message.please_enter_last_name')}}" },
                email: { required: "{{__('message.please_enter_email')}}", email: "{{__('message.please_enter_valid_email')}}" },
                username: { required: "{{__('message.please_enter_username')}}" },
                password: { required: "{{__('message.please_enter_password')}}" },
                contact_number: { required: "{{__('message.please_enter_contact_number')}}" },
                gender: { required: "{{__('message.please_select_gender')}}" },
            });
        });        
    </script>
@endsection
