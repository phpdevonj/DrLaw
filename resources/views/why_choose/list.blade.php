<div class="col-lg-12">
    <h4 class="modal-title">{{__('message.list')}}</h4>
    <br>
    @foreach($data as $item)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <img src="{{ getSingleMedia($item, 'frontend_data_image') }}" class="avatar-100 img-fluid rounded">
                    </div>
                    <div class="col-sm-10">
                        <div class="pt-2"><strong>{{__('message.title')}}</strong>: {{ $item->title }}</div>
                        <div class="pt-2"><strong>{{__('message.description')}}</strong>: {{ $item->description }}</div>
                    </div>
                    <div class="col text-right d-flex justify-content-end">
                        <a class="mr-2 loadRemoteModel" href="{{ route('why-choose.edit', $item->id) }}" title="{{ __('message.update_form_title',['form' => __('message.why_choose') ]) }}"><i class="fas fa-edit text-primary"></i></a>
                        {{ Form::open(['route' => ['why-choose.destroy',  $item->id], 'method' => 'delete','data--submit'=>'why_choose'. $item->id]) }}
                            <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="why_choose{{ $item->id}}" 
                                data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.why_choose') ]) }}"
                                title="{{ __('message.delete_form_title',['form'=>  __('message.why_choose') ]) }}"
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