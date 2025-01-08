<div class="col-lg-12">
    <h4 class="modal-title">{{__('message.our_mission_description')}}</h4>
    <br>
    @foreach($data as $item)
    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-sm-10">
                    <div class="pt-2"><strong>{{__('message.description')}}</strong>: {{ $item->description }}</div>
                </div>
                <div class="col text-right d-flex justify-content-end">
                    <a class="mr-2 loadRemoteModel" href="{{ route('our-mission.edit', $item->id) }}" title="{{ __('message.update_form_title',['form' => __('message.our_mission') ]) }}"><i class="fas fa-edit text-primary"></i></a>
                    {{ Form::open(['route' => ['our-mission.destroy',  $item->id], 'method' => 'delete','data--submit'=>'our_mission'. $item->id]) }}
                        <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="our_mission{{ $item->id}}" 
                            data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.our_mission') ]) }}"
                            title="{{ __('message.delete_form_title',['form'=>  __('message.our_mission') ]) }}"
                            data-message='{{ __("message.delete_msg") }}'>
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
