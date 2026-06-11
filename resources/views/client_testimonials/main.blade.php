<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('message.list') }}</h5>
                            <a href="{{ route('client-testimonials.create') }}"class="float-right btn btn-sm btn-primary loadRemoteModel">{{ __('message.add_form_title', ['form' => __('message.client_testimonials')]) }}</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['method' => 'POST', 'route' => [ 'frontend.website.information.update', 'client_testimonials'], 'enctype' => 'multipart/form-data', 'data-toggle'=>'validator']) }}
                            <div class="row">
                                @foreach($client_testimonials as $key => $value)
                                    @if( in_array( $key, ['title', 'subtitle'] ))
                                        <div class="col-md-6 form-group">
                                            {{ Form::label($key, __('message.'.$key),['class'=>'form-control-label'] ) }}
                                            {{ Form::text($key, $value ?? null,[ 'placeholder' =>  __('message.'.$key), 'class' => 'form-control' ]) }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <hr>
                            <div class="col-md-12 mt-1 mb-4">
                                <button class="btn btn-md btn-primary float-md-right" id="saveButton">{{ __('message.save') }}</button>
                            </div>
                        {{ Form::close() }}
                    </div>
                    @if(count($data) > 0)
                        @include('client_testimonials.list')
                    @endif
                    <br>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
