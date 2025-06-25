<?php
    $auth_user= authSession();
?>
{{ Form::open(['route' => ['admin-user.destroy', $id], 'method' => 'delete','data--submit'=>'driver'.$id]) }}
<div class="d-flex justify-content-end align-items-center">
    {{-- @if($auth_user->can('driver edit')) --}}
    <a class="mr-2" href="{{ route('admin-user.edit', $id) }}" title="{{ __('message.update_form_title',['form' => __('message.admin_user') ]) }}"><i class="fas fa-edit text-primary"></i></a>
    {{-- @endif --}}
    
    {{-- @if( $data->status == 'active' && $auth_user->can('driver show') ) --}}
        <!-- <a class="mr-2" href="{{ route('admin-user.show',$id) }}"><i class="fas fa-eye text-secondary"></i></a> -->
    {{-- @endif

    @if($auth_user->can('driver delete')) --}}
    <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="driver{{$id}}" 
        data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.admin_user') ]) }}"
        title="{{ __('message.delete_form_title',['form'=>  __('message.admin_user') ]) }}"
        data-message='{{ __("message.delete_msg") }}'>
        <i class="fas fa-trash-alt"></i>
    </a>
    {{-- @endif --}}
</div>
{{ Form::close() }}