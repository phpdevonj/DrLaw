<?php
    $auth_user = authSession();
?>
{{ Form::open(['route' => ['useraddress.destroy', $id], 'method' => 'delete','data--submit'=>'useraddress'.$id]) }}
<div class="d-flex justify-content-end align-items-center">
    <a class="mr-2" href="{{ route('useraddress.edit', $id) }}" title="{{ __('message.update_form_title',['form' => __('message.user_address') ]) }}"><i class="fas fa-edit text-primary"></i></a>
    <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="useraddress{{$id}}" 
        data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.user_address') ]) }}"
        title="{{ __('message.delete_form_title',['form'=>  __('message.user_address') ]) }}"
        data-message='{{ __("message.delete_msg") }}'>
        <i class="fas fa-trash-alt"></i>
    </a>
</div>
{{ Form::close() }}