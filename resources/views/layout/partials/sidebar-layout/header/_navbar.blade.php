 <div class="app-navbar flex-shrink-0">
     <div class="app-navbar-item align-items-stretch ms-1 ms-md-3">
         @include(config('settings.KT_THEME_LAYOUT_DIR') . '/partials/sidebar-layout/search/_dropdown')
     </div>
     <div class="app-navbar-item ms-1 ms-md-4">
         <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
             id="kt_activities_toggle">{!! getIcon('messages', 'fs-2') !!}</div>
     </div>
     <div class="app-navbar-item ms-1 ms-md-4">
         <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
             data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
             data-kt-menu-placement="bottom-end" id="kt_menu_item_wow">{!! getIcon('notification-status', 'fs-2') !!}</div>
         @include('partials/menus/_notifications-menu')
     </div>
     <div class="app-navbar-item ms-1 ms-md-4">
         <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px position-relative"
             id="kt_drawer_chat_toggle">{!! getIcon('message-text-2', 'fs-2') !!}
             <span
                 class="bullet bullet-dot bg-success h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
         </div>
     </div>
     <div class="app-navbar-item ms-1 ms-md-4">
         <div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
             data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent"
             data-kt-menu-placement="bottom-end">{!! getIcon('element-11', 'fs-2') !!}</div>
         @include('partials/menus/_my-apps-menu')
     </div>
     <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
         <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
             data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
             @if (Auth::user()->profile_photo_url)
                 <img src="{{ \Auth::user()->profile_photo_url }}" class="rounded-3" alt="user" />
             @else
                 <div
                     class="symbol-label fs-3 {{ app(\App\Actions\GetThemeType::class)->handle('bg-light-? text-?', Auth::user()->name) }}">
                     {{ substr(Auth::user()->name, 0, 1) }}
                 </div>
             @endif
         </div>
         @include('partials/menus/_user-account-menu')
     </div>
     <div class="app-navbar-item d-lg-none ms-2 me-n2" title="Show header menu">
         <div class="btn btn-flex btn-icon btn-active-color-primary w-30px h-30px" id="kt_app_header_menu_toggle">
             {!! getIcon('element-4', 'fs-1') !!}</div>
     </div>
 </div>
