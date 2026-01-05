<div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2" title="Show sidebar menu">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
            </div>
        </div>
        <div class="d-flex align-items-stretch justify-content-end flex-lg-grow-1">
            <div class="app-navbar flex-shrink-0">
                <div class="app-navbar-item ms-1 ms-md-4">
                    <a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-night-day theme-light-show fs-1"></i>
                        <i class="ki-outline ki-moon theme-dark-show fs-1"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-night-day fs-2"></i>
                                </span>
                                <span class="menu-title">Light</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-moon fs-2"></i>
                                </span>
                                <span class="menu-title">Dark</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-outline ki-screen fs-2"></i>
                                </span>
                                <span class="menu-title">System</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
                   <div class="cursor-pointer symbol symbol-35px"
                        data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
                        data-kt-menu-attach="parent"
                        data-kt-menu-placement="bottom-end">
                        
                        @if(Auth::user()->photo)
                            <img src="{{ asset('storage/' . Auth::user()->photo) }}" class="rounded-3" alt="user" />
                        @else
                            <img src="{{ asset('assets/media/avatars/default.jpg') }}" class="rounded-3" alt="user" />
                        @endif
                    </div>

                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                     @if(Auth::user()->photo)
                                            <img src="{{ asset('storage/' . Auth::user()->photo) }}" class="rounded-3" alt="user" />
                                        @else
                                            <img src="{{ asset('assets/media/avatars/default.jpg') }}" class="rounded-3" alt="user" />
                                        @endif
                                </div>
                               <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">
                                        {{ Auth::user()->name }}
                                        <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ Auth::user()->role }}</span>
                                    </div>
                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">
                                        {{ Auth::user()->email }}
                                    </a>
                                </div>
                            </div>
                            
                            <div class="menu-item mt-3">
                                <a class="menu-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-exit-right fs-3"></i>
                                    </span>
                                    <span class="menu-title">Logout</span>
                                </a>
                            </div>

                            <form id="logout-form" action="/logout" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                        <div class="separator my-2"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
