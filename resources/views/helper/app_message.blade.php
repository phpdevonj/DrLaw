<script type="text/javascript">
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        iconColor: 'success',
        customClass: {
            popup: 'colored-toast',
        },
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
    });

    @if (Session::has('success'))
        Toast.fire({
            icon: 'success',
            title: '{{ Session::get("success") }}',
        });
    @endif

    @if (Session::has('error'))
        Toast.fire({
            icon: 'error',
            title: '{{ Session::get("error") }}',
        });
    @endif

    @if(Session::has('errors') || ( isset($errors) && is_array($errors) && $errors->any()))
        Toast.fire({
            icon: 'error',
            title: '{{ Session::get("errors")->first() }}',
        });
    @endif
</script>