@extends('layout.master')

@section('content')
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
                style="background-image: url({{ image('misc/auth-bg.png') }})">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                    <a href="{{ route('dashboard') }}" class="mb-12">
                        <img alt="Logo" src="{{ image('logos/custom-1.png') }}" class="h-60px h-lg-75px" />
                    </a>

                    <img class="d-none d-lg-block mx-auto w-175px w-md-50 w-xl-300px mb-10 mb-lg-20"
                        src="{{ image('misc/auth-screens.png') }}" alt="" />

                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">
                        Fast, Efficient and Productive
                    </h1>

                    <div class="d-none d-lg-block text-white fs-base text-center">
                        Where <a href="#" class="opacity-75-hover text-warning fw-bold me-1">beauty</a>
                        meets smarter business management. <br />

                        Simplify appointments, empower your
                        <a href="#" class="opacity-75-hover text-warning fw-bold me-1">team</a>
                        and create exceptional <br />

                        experiences for every client.
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
