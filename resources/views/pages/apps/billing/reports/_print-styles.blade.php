{{-- Prints only the report: hides the app chrome and anything marked .no-print. --}}
@push('styles')
    <style>
        @media print {
            #kt_app_header,
            #kt_app_sidebar,
            #kt_app_toolbar,
            #kt_app_footer,
            .no-print {
                display: none !important;
            }

            #kt_app_wrapper,
            #kt_app_main,
            .app-wrapper,
            .app-main {
                margin: 0 !important;
                padding: 0 !important;
            }

            .card {
                box-shadow: none !important;
            }
        }
    </style>
@endpush
