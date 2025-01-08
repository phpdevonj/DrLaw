<x-master-layout>
<div class="container-fluid">
    <div class="row">            
        <div class="col-lg-12">
            <div class="card card-block card-stretch border-radius-20">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <h5 class="font-weight-bold">{{ $pageTitle }}</h5>
                        <a href="{{ route('rider.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('message.back') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-radius-20">
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a href="{{ route('rider.show',$data->id) }}" class="nav-link {{ $type == 'detail' ? 'active': '' }}"> {{ __('message.detail_form_title',['form'=>__('message.rider')]) }} </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('rider.show', [ $data->id, 'type' => 'wallet_history']) }}" class="nav-link {{ $type == 'wallet_history' ? 'active': '' }}"> {{ __('message.wallethistory') }} </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('rider.show', [ $data->id, 'type' => 'ride_request']) }}" class="nav-link {{ $type == 'ride_request' ? 'active': '' }}"> {{ __('message.riderequest') }} </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('rider.show', [ $data->id, 'type' => 'withdraw_request']) }}" class="nav-link {{ $type == 'withdraw_request' ? 'active': '' }}"> {{ __('message.withdrawrequest') }} </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @if( $type == 'detail' )
            <div class="col-lg-4">
                <div class="card card-block p-card border-radius-20">
                    <div class="profile-box">
                        <div class="profile-card border-radius-20">
                            <img src="{{ $profileImage }}" alt="01.jpg" class="avatar-100 rounded d-block mx-auto img-fluid mb-3">
                            <h3 class="font-600 text-white text-center mb-0">{{ $data->display_name }}</h3>
                            <p class="text-white text-center mb-5">

                                @php
                                    $status = 'warning';
                                    switch ($data->status) {
                                        case 'active':
                                            $status = 'success';
                                            break;
                                        case 'inactive':
                                            $status = 'danger';
                                            break;
                                        case 'banned':
                                            $status = 'dark';
                                            break;
                                    }
                                @endphp

                                <span class="text-capitalize badge bg-{{ $status }} ">{{ $data->status }}</span>
                            </p>
                            @php
                                $rating = $data->rating ?? 0;
                                $fullStars = floor($rating);
                                $halfStar = $rating - $fullStars;
                            @endphp
                            @if($rating > 0)
                                <div class="d-flex justify-content-center mt-0 {{ $data->rating ? 'mb-5' : '' }}">
                                    <div class="d-inline-flex rounded">
                                        @for ($i = 0; $i < $fullStars; $i++)
                                            <i class="fas fa-star mt-1" style="color: yellow"></i>
                                        @endfor
                                        @if ($halfStar > 0)
                                            <i class="fas fa-star-half mt-1" style="color: yellow"></i>
                                        @endif
                                        <span class="ml-2 font-600 text-white text-center mb-0">{{ "(" .number_format($rating, 1) . ")" }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="pro-content rounded border-radius-20">
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-icon mr-3"> 
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <p class="mb-0 eml">{{ maskSensitiveInfo('email', $data->email) }}</p>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-icon mr-3"> 
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <p class="mb-0">{{ maskSensitiveInfo('contact_number', $data->contact_number) }}</p>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-icon mr-3">
                                    <i class="fa fa-code-branch"></i>
                                </div>
                                <p class="mb-0">{{ __('message.app_version') . ' : ' . (auth()->user()->hasRole('admin') ? ($data->app_version ? : '0') : '0')}}</p>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="p-icon mr-3"> 
                                    
                                    @if( $data->gender == 'female' )
                                        <i class="fas fa-female"></i>
                                    @elseif( $data->gender == 'other' )
                                        <i class="fas fa-transgender"></i>
                                    @else
                                        <i class="fas fa-male"></i>
                                    @endif
                                </div>
                                <p class="mb-0">{{ $data->gender }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card border-radius-20">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h4 class="card-title mb-0">{{ __('message.detail_form_title', [ 'form' => __('message.bank') ]) }}</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.bank_name') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->bank_name ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.bank_code') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->bank_code ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.account_holder_name') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->account_holder_name ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.account_number') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->account_number ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.bank_address') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->bank_address ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.routing_number') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->routing_number ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.bank_iban') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->bank_iban ?? '-' }}</p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <h5>{{ __('message.bank_swift') }}</h5>
                                                <p class="mb-0">{{ optional($data->userBankAccount)->bank_swift ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        @if( $type == 'wallet_history' )
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-block border-radius-20">
                            <div class="card-body">
                                <div class="top-block-one">                                
                                    <p class="mb-1">{{ __('message.wallet_balance') }}</p>
                                    <p></p>
                                    <h5>{{ getPriceFormat(optional($data->userWallet)->total_amount) ?? 0 }} </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{--
                    <div class="col-md-4">
                        <div class="card card-block">
                            <div class="card-body">
                                <div class="top-block-one">
                                    <div class="">
                                        <p class="mb-1">{{ __('message.total_withdraw') }}</p>
                                        <p></p>
                                        <h5>{{ getPriceFormat(optional($data->userWallet)->total_withdraw) ?? 0 }} </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    --}}
                </div>
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.add_form_title', [ 'form' => __('message.wallet') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => ['savewallet.fund', $data->id], 'method' => 'post' ]) !!}
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('type', __('message.type').' <span class="text-danger">*</span>',[ 'class' => 'form-control-label' ], false) }}
                                    {{ Form::select('type', [ 'credit' => __('message.credit'), 'debit' => __('message.debit') ], old('type'), [ 'class' => 'form-control select2js', 'required']) }}
                                </div>

                                <div class="form-group col-md-8">
                                    {{ Form::label('transaction_type', __('message.transaction_type').' <span class="text-danger">*</span>',[ 'class' => 'form-control-label' ], false) }}
                                    {{ Form::select('transaction_type',[], old('transaction_type'), [ 'class' => 'form-control select2js', 'required']) }}
                                </div>

                                <div class="form-group col-md-12">
                                    {{ Form::label('amount', __('message.amount').' <span class="text-danger">*</span>', ['class' => 'form-control-label' ], false ) }}
                                    {{ Form::number('amount', old('amount'), [ 'class' => 'form-control', 'min' => 0, 'step' => 'any', 'required', 'placeholder' => __('message.amount') ]) }}
                                </div>

                                <div class="form-group col-md-12">
                                    {{ Form::label('description', __('message.description'), ['class' => 'form-control-label']) }}
                                    {{ Form::textarea('description', null, [ 'class' => 'form-control textarea', 'rows' => 2, 'placeholder' => __('message.description') ]) }}
                                </div>
                            </div>
                            <hr>
                            {{ Form::submit( __('message.save'), ['class'=>'btn btn-md btn-primary float-right' ]) }}
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.list_form_title', [ 'form' => __('message.wallethistory') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        @endif
        @if( $type == 'ride_request' )
            <div class="col-md-12">
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.list_form_title', [ 'form' => __('message.riderequest') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        @endif
        @if( $type == 'withdraw_request' )
            <div class="col-md-12">
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.list_form_title', [ 'form' => __('message.riderequest') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['class' => 'table  w-100'],false) }}
                    </div>
                </div>
            </div>
        @endif
    </div> 
</div>
@section('bottom_script')
    {{ in_array($type,['ride_request','wallet_history','withdraw_request']) ? $dataTable->scripts() : '' }}
    <script type="text/javascript">
        (function($) {
            "use strict";
            $(document).ready(function() {
                
                var type = $("#type :selected").val();
                transactionTypeList(type);
                $(document).on('change', '#type' , function (){
                    var type = $("#type :selected").val();
                    $('#transaction_type').empty();
                    transactionTypeList(type);
                })
            })

            function transactionTypeList(type) {
                var route = "{{ route('ajax-list',['type' => 'transaction_type','user_type' => 'rider', 'type_val' =>'']) }}"+type;
                route = route.replaceAll('amp;','');
                
                $.ajax({
                    url: route,
                    success: function(result){
                        $('#transaction_type').select2({
                            width : '100%',
                            placeholder: "{{ __('message.select_name',['select' => __('message.transaction_type')]) }}",
                            data: result.results
                        });
                        if(type != null ){
                            $("#transaction_type").val(type).trigger('change');
                        }
                    }
                })
            }
        })(jQuery);
    </script>
@endsection
</x-master-layout>