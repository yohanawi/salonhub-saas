@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Swal === 'undefined') {
                return;
            }

            @if (session('status'))
                Swal.fire({
                    text: @js(session('status')),
                    icon: 'success',
                    buttonsStyling: false,
                    confirmButtonText: 'Ok, got it',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                });
            @endif

            @if ($errors->any())
                Swal.fire({
                    title: 'Please check the form',
                    text: @js($errors->first()),
                    icon: 'error',
                    buttonsStyling: false,
                    confirmButtonText: 'Ok, I will fix it',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                });
            @endif

            document.querySelectorAll('form[data-swal-confirm]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (form.dataset.swalSubmitting === 'true') {
                        return;
                    }

                    event.preventDefault();

                    Swal.fire({
                        title: form.dataset.swalTitle || 'Are you sure?',
                        text: form.dataset.swalText || 'This action cannot be undone.',
                        icon: form.dataset.swalIcon || 'warning',
                        showCancelButton: true,
                        buttonsStyling: false,
                        confirmButtonText: form.dataset.swalConfirmButton || 'Yes, continue',
                        cancelButtonText: form.dataset.swalCancelButton || 'Cancel',
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-light',
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.dataset.swalSubmitting = 'true';
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
