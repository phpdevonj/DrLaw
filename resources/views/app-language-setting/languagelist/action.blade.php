<?php
$auth_user = authSession();
?>
{{ Form::open(['route' => ['languagelist.destroy', $id], 'method' => 'delete', 'data--submit' => 'languagelist' . $id]) }}
<div class="d-flex justify-content-end align-items-center">
    @if ($auth_user->can('languagelist-edit'))
        <a class="mr-2" href="{{ route('languagelist.edit', $id) }}"
            title="{{ __('message.update_form_title', ['form' => __('message.language')]) }}"><i
                class="fas fa-edit text-primary"></i></a>
    @endif

    @if ($auth_user->can('languagelist-delete'))
        <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="languagelist{{ $id }}"
            data--confirmation='true'
            data-title="{{ __('message.delete_form_title', ['form' => __('message.language')]) }}"
            title="{{ __('message.delete_form_title', ['form' => __('message.language')]) }}"
            data-message='{{ __('message.delete_msg') }}'>
            <i class="fas fa-trash-alt"></i>
        </a>
    @endif
</div>
{{ Form::close() }}
