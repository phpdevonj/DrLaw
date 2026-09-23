<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['car-models.update', $id], 'method' => 'patch' ]) !!}
        @else
            {!! Form::open(['route' => ['car-models.store'], 'method' => 'post' ]) !!}
        @endif
        <div class="row">
            <div class="col-12">
                <a href="{{route('car-models.index')}}" class="btn border-radius-10 btn-dark float-right" role="button"><i class="fas fa-arrow-circle-left"></i> {{ __('message.back') }}</a>
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
                                <div class="form-group col-md-4">
                                    {{ Form::label('name', __('message.name').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::text('name', old('name'),[ 'placeholder' => __('message.name'),'class' =>'form-control','required']) }}
                                </div>                                
                            </div>
                            <hr>
                            {{ Form::button('<span id="button-loader" style="display:none;"><div class="spinner-border spinner-border-sm text-light" role="status"></div></span> ' . __('message.save'), [
                                'type' => 'submit',
                                'class' => 'btn border-radius-10 btn-success float-right',
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
    @endsection
</x-master-layout>