<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.riderequest') }}</h4>
                        </div>
                        <h4 class="float-right">#{{ $data->id }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h4>{{ __('message.pickup_address') }}</h4>
                                <p>{{ $data->start_address }}</p>

                            </div>
                            @if(!empty($data->multi_drop_location) && $data->multi_drop_location != null)
                                @php
                                    $multiDropLocations = json_decode($data->multi_drop_location, true);
                                @endphp

                                <div class="col-12 timeline">
                                    @if(is_array($multiDropLocations))
                                        @foreach($multiDropLocations as $item)
                                            <div class="timeline-item">
                                                <div class="timeline-content">
                                                    <div class="timeline-icon">
                                                        <i class="ri-map-pin-line text-dark"></i>
                                                    </div>
                                                    <div class="timeline-text">
                                                        <p>{{ $item['address'] ?? '-' }} <br>
                                                            <small class="p-0">{{ __('message.dropped_at') }}: {{ date('Y-m-d H:i', strtotime($item['dropped_at'])) ?? '-' }}</small>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="timeline-no-location">{{ __('message.no_multi_drop_location') }}</p>
                                    @endif
                                </div>
                            @endif
                        
                            @if( !empty($data->multi_drop_location) && count($data->multi_drop_location) > 0)
                                @foreach ($data->multi_drop_location as $key => $value)
                                    <div class="col-12">
                                        <p><i class="ri-map-pin-line text-success"></i> {{ $value['address'] ?? '-' }}</p>
                                    </div>
                                @endforeach
                            @endif
                            <div class="col-12">
                                <h4>{{ __('message.drop_address') }}</h4>
                                <p>{{ $data->end_address }}</p>
                            </div>
                        </div>
                        @if(optional($data)->payment != null && optional($data)->payment->payment_status == 'paid')
                            <hr>
                            <div class="row">
                                <div class="col-3">
                                    <p>{{ __('message.total_distance') }}</p>
                                    {{ $data->distance }} {{ $data->distance_unit }}
                                </div>
                                <div class="col-3">
                                    <p>{{ __('message.total_duration') }}</p>
                                    {{ $data->duration }} {{ __('message.min') }}
                                </div>
                                <div class="col-3">
                                    <p>{{ __('message.admin_commission') }}</p>
                                    {{ getPriceFormat(optional($data->payment)->admin_commission ?? 0 ) }}
                                </div>
                                <div class="col-3">
                                    <p>{{ __('message.driver_earning') }}</p>
                                    {{ getPriceFormat(optional($data->payment)->driver_commission ?? 0 ) }}
                                </div> 
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.payment') }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {{--  @dd($data->ride_has_bid == 1)  --}}
                        @if(optional($data)->payment != null && optional($data)->payment->payment_status == 'paid')
                            @php
                            $distance_unit = $data->distance_unit;
                            @endphp
                            @if ($data->ride_has_bid == 1)
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.payment_method') }}</span>
                                        <span class="font-weight-bold">{{ $data->payment_type ?? '-' }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.bid_amount') }}</span>
                                        <span class="font-weight-bold">{{ $data->approvedBids->bid_amount }}</span>
                                    </li>
                                </ul>
                            @else
                                <ul class="list-group list-group-flush">
                                    @if($data->minimum_fare == ( $data->subtotal - $data->extra_charges_amount ))
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.minimum_fare') }}</span>
                                        <span></span>
                                        <span class="">{{ getPriceFormat($data->minimum_fare) }}</span>
                                    </li>                                
                                    @else
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.base_fare') }}</span>
                                        <span>{{ __('message.for_first') }} {{ $data->base_distance }} {{ __('message.'.$distance_unit) }}</span>
                                        <span class="">{{ getPriceFormat($data->base_fare) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.distance') }}</span>
                                        @if($data->distance > $data->base_distance)
                                            <span>{{ $data->distance - $data->base_distance }} {{ $distance_unit }} x {{ $data->per_distance }}/{{ __('message.'.$distance_unit) }}</span>
                                        @else
                                            <span>{{ $data->distance }} {{ $distance_unit }} x {{ $data->per_distance }}/{{ __('message.'.$distance_unit) }}</span>
                                        @endif
                                        <span class="">{{ getPriceFormat($data->per_distance_charge) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.duration') }}</span>
                                        <span>{{ $data->duration }} {{ __('message.min') }} x {{ $data->per_minute_drive }}/{{ __('message.min') }}</span>
                                        <span class="">{{ getPriceFormat($data->per_minute_drive_charge) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.wait_time') }}</span>
                                        @if($data->waiting_time == 0)
                                            <span></span>
                                        @else
                                            <span>{{ $data->waiting_time }} {{ __('message.min') }} x {{ $data->per_minute_waiting }}/{{ __('message.min') }}</span>
                                        @endif
                                        <span class="">{{ getPriceFormat($data->per_minute_waiting_charge) }}</span>
                                    </li>
                                    @endif
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.extra_charges') }}</span>
                                        @if(count($data->extra_charges) > 0)
                                            @php
                                                $extra_charges = collect($data->extra_charges)->pluck('key')->implode(', ');
                                            @endphp
                                            <span>{{ $extra_charges }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        <span class="">{{ getPriceFormat($data->extra_charges_amount) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.tip') }}</span>
                                        <span></span>
                                        <span class="">{{ getPriceFormat($data->tips) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.coupon_discount') }}</span>
                                        <span></span>
                                        <span class="">{{ getPriceFormat($data->coupon_discount) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.fixed_charges') }}</span>
                                        <span></span>
                                        <span class="">{{ getPriceFormat($fixed_amount) }}</span>
                                    </li>
                                    <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                        <span>{{ __('message.total_amount') }}</span>
                                        @php
                                            $surge_price_setting_value = SettingData('ride', 'surge_price') ?? null;
                                            if ($surge_price_setting_value == 1){
                                                $total_amount = ( $data->tips ?? 0 ) + optional($data->payment)->total_amount + ($fixed_amount ? $fixed_amount : 0);
                                            } else {
                                                $total_amount = ( $data->tips ?? 0 ) + optional($data->payment)->total_amount;
                                            }
                                        @endphp
                                        <span class="font-weight-bold">{{ getPriceFormat($total_amount) }}</span>
                                    </li>
                                </ul>
                            @endif                                
                        @else
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                    <span>{{ __('message.payment_method') }}</span>
                                    <span class="font-weight-bold">{{ $data->payment_type ?? '-' }}</span>
                                </li>
                                <li class="list-group-item d-flex flex-xl-row flex-column justify-content-between align-items-center align-items-xl-start px-0"> 
                                    <span>{{ __('message.amount') }}</span>
                                    <span class="font-weight-bold">{{ optional($data->payment)->total_amount == null ? '-' : getPriceFormat(optional($data->payment)->total_amount) }}</span>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>
                @if(count($data->rideRequestHistory) > 0)
                    <div class="card card-block border-radius-20">
                        <div class="card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="card-title mb-0">{{ __('message.activity_timeline') }}</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mm-timeline0 m-0 d-flex align-items-center justify-content-between position-relative">
                                <ul class="list-inline p-0 m-0">
                                    @php
                                        $sequence = [
                                            'new_ride_requested',
                                            'bid_placed',
                                            'bid_rejected',
                                            'bid_accepted',
                                            'arrived',
                                            'in_progress',
                                            'completed',
                                            'payment_status_message',
                                        ];
                    
                                        $bidPlacedDrivers = [];
                                        $bidRejectedDrivers = [];
                                        $historyEntries = [];
                    
                                        $colorMapping = [
                                            'new_ride_requested' => 'border-info text-info',
                                            'bid_placed' => 'border-success text-success',
                                            'bid_rejected' => 'border-danger text-danger',
                                            'bid_accepted' => 'border-success text-success',
                                            'arrived' => 'border-warning text-warning',
                                            'in_progress' => 'border-primary text-primary',
                                            'completed' => 'border-secondary text-secondary',
                                            'payment_status_message' => 'border-dark text-dark',
                                            'driver_declined' => 'border-danger text-danger',
                                        ];
                    
                                        foreach ($data->rideRequestHistory as $history) {
                                            $historyData = is_string($history->history_data) ? json_decode($history->history_data, true) : $history->history_data; // Decode as associative array
                    
                                            if ($history->history_type === 'driver_declined') {
                                                $historyEntries[] = [
                                                    'type' => 'driver_declined',
                                                    'message' => '<a href="'. route('driver.show', ['driver' => $history->rideRequest->riderequest_in_driver_id]) .'">'. $history->history_message .'</a>',
                                                    'datetime' => $history->datetime,
                                                ];
                                            } elseif (in_array($history->history_type, ['bid_placed', 'bid_rejected'])) {
                                                $driverName = $historyData['driver_name'] ?? '';
                                                $driverId = $historyData['driver_id'] ?? '';
                                                $driverLink = '<a href="'. route('driver.show', ['driver' => $driverId]) .'">'.  $driverName . ' ('.$driverId .')' . '</a>';
                    
                                                if ($history->history_type === 'bid_placed') {
                                                    $bidPlacedDrivers[] = $driverLink;
                                                } else {
                                                    $bidRejectedDrivers[] = $driverLink;
                                                }
                                            } else {
                                                $historyEntries[] = [
                                                    'type' => $history->history_type,
                                                    'message' => $history->history_message,
                                                    'datetime' => $history->datetime,
                                                ];
                                            }
                                        }
                    
                                        if ($bidPlacedDrivers) {
                                            $historyEntries[] = [
                                                'type' => 'bid_placed',
                                                'message' => 'Placed bids drivers: ' . implode(' , ', $bidPlacedDrivers),
                                                'datetime' => now(),
                                            ];
                                        }
                    
                                        if ($bidRejectedDrivers) {
                                            $historyEntries[] = [
                                                'type' => 'bid_rejected',
                                                'message' => 'Rejected bids: ' . implode(' , ', $bidRejectedDrivers),
                                                'datetime' => now(),
                                            ];
                                        }
                    
                                        usort($historyEntries, function ($a, $b) use ($sequence) {
                                            return array_search($a['type'], $sequence) - array_search($b['type'], $sequence);
                                        });
                                    @endphp
                    
                                    @foreach($historyEntries as $entry)
                                        @php
                                            // Get the color class based on the type
                                            $colorClass = $colorMapping[$entry['type']] ?? 'border-primary text-primary';
                                        @endphp
                                        <li>
                                            <div class="timeline-dots1 {{ $colorClass }}"></div>
                                            <h6 class="float-left mb-1">{{ __('message.' . $entry['type']) }}</h6>
                                            <small class="float-right mt-1">{{ $entry['datetime'] }}</small>
                                            <div class="d-inline-block w-100">
                                                <p>{!! $entry['message'] !!}</p>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                
                @endif
            </div>
            <div class="col-lg-4">
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.detail_form_title', [ 'form' => __('message.rider') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <img src="{{ getSingleMedia(optional($data->rider), 'profile_image',null) }}" alt="rider-profile" class="img-fluid avatar-60 rounded-small">
                            </div>
                            <div class="col-9">
                                @if( $data->is_ride_for_other == 0 )
                                <p class="mb-0">{{ optional($data->rider)->display_name }}</p>
                                <p class="mb-0">{{ optional($data->rider)->contact_number }}</p>
                                <p class="mb-0">{{ optional($data->rider)->email }}</p>
                                <p class="mb-0">{{ optional($data->rideRequestRiderRating())->rating }}
                                    @if( optional($data->rideRequestRiderRating())->rating > 0 )
                                        <i class="fa fa-star" style="color: yellow"></i>
                                    @endif
                                </p>
                                @else
                                    <p class="mb-0"><b>{{ __('message.booked_by') }}:</b> {{ optional($data->rider)->display_name }}</p>
                                    @if(!empty($data->other_rider_data))
                                        @foreach($data->other_rider_data as $key)
                                            <p class="mb-0">{{ $key ?? '' }}</p>
                                        @endforeach                                    
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if( isset($data->driver) )
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.detail_form_title', [ 'form' => __('message.driver') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <img src="{{ getSingleMedia(optional($data->driver), 'profile_image',null) }}" alt="driver-profile" class="img-fluid avatar-60 rounded-small">
                            </div>
                            <div class="col-9">
                                <p class="mb-0">{{ optional($data->driver)->display_name }}</p>
                                <p class="mb-0">{{ optional($data->driver)->contact_number }}</p>
                                <p class="mb-0">{{ optional($data->driver)->email }}</p>
                                <p class="mb-0">{{ optional($data->rideRequestDriverRating())->rating }}
                                    @if( optional($data->rideRequestDriverRating())->rating > 0 )
                                        <i class="fa fa-star" style="color: yellow"></i>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="card card-block border-radius-20">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ __('message.detail_form_title', [ 'form' => __('message.service') ]) }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <img src="{{ getSingleMedia($data->service, 'service_image',null) }}" alt="service-detail" class="img-fluid avatar-60 rounded-small">
                            </div>
                            <div class="col-9">
                                <p class="mb-0">{{ optional($data->service)->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>