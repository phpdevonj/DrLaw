<?php
$auth_user = authSession();
?>
<div class="d-flex justify-content-end align-items-center">
    @if ($auth_user->can('languagewithkeyword-edit'))
        <a class="mr-2 loadRemoteModel" href="{{ route('languagewithkeyword.edit', $id) }}"
            title="{{ __('message.update_form_title', ['form' => __('message.language_with_keyword')]) }}"><i
                class="fas fa-edit text-primary"></i></a>
    @endif
</div>
