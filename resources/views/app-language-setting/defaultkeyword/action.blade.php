<?php
$auth_user = authSession();
?>
<div class="d-flex justify-content-end align-items-center">
    @if ($auth_user->can('defaultkeyword-edit'))
        <a class="mr-2 loadRemoteModel" href="{{ route('defaultkeyword.edit', $id) }}"
            title="{{ __('message.update_form_title', ['form' => __('message.defaultkeyword')]) }}"><i
                class="fas fa-edit text-primary"></i></a>
    @endif
</div>
