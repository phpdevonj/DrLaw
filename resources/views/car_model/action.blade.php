<?php
    $auth_user= authSession();
?>
{{ Form::open(['route' => ['car-models.destroy', $id], 'method' => 'delete','data--submit'=>'car_model'.$id]) }}
<div class="d-flex justify-content-end align-items-center">
   <a class="mr-2" href="{{ route('car-models.edit', $id) }}" title="{{ __('message.update_form_title',['form' => __('message.car_model') ]) }}"><i class="fas fa-edit text-primary"></i></a>
    
    <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="car_model{{$id}}" 
        data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.car_model') ]) }}"
        title="{{ __('message.delete_form_title',['form'=>  __('message.car_model') ]) }}"
        data-message='{{ __("message.delete_msg") }}'>
        <i class="fas fa-trash-alt"></i>
    </a>
</div>
{{ Form::close() }}