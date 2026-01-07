<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    
    {{-- ============================================
        LOGO SECTION
    ============================================ --}}
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate"
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-outline ki-black-left-line fs-3 rotate-180"></i>
        </div>
    </div>

    {{-- ============================================
        MENU SECTION
    ============================================ --}}
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                
                    {{-- ============================================
                        DASHBOARD MENU
                    ============================================ --}}
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}" 
                        href="{{ route('dashboard.admin') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-element-11 fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </div>

                    {{-- ============================================
                        DIVIDER
                    ============================================ --}}
                    <div class="menu-item">
                        <div class="menu-content pt-8 pb-2">
                            <span class="menu-section text-muted text-uppercase fs-8 ls-1">Management</span>
                        </div>
                    </div>

                    @can('manage-users')
                    <div class="menu-item">
                        <a class="menu-link" href="{{ route('manage-user.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-people fs-2"></i>
                            </span>
                            <span class="menu-title">User</span>
                        </a>
                    </div>
                    @endcan

                    <div class="menu-item">
                        <a class="menu-link" 
                        href="{{ route('manage-mutasicompany.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-arrows-loop fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Mutasi Company</span>
                        </a>
                    </div>

                    @can('karyawan-sync')
                    <div class="menu-item">
                        <a class="menu-link" href="{{ route('karyawan.sync.dashboard') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-badge fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title">Karyawan</span>
                        </a>
                    </div>
                    @endcan

                    {{-- ============================================
                        PTKP & PERIODE KARYAWAN GROUP (ACCORDION)
                    ============================================ --}}
                    @if(Gate::check('ptkp-sync') || Gate::check('ptkp-history-sync') || Gate::check('periode-karyawan'))
                    <div data-kt-menu-trigger="click" 
                         class="menu-item menu-accordion {{ request()->routeIs('ptkp.*') || request()->routeIs('periode-karyawan.*') ? 'here show' : '' }}">
                        
                        {{-- Parent Menu --}}
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-document fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Data Karyawan</span>
                            <span class="menu-arrow"></span>
                        </span>

                        {{-- Submenu --}}
                        <div class="menu-sub menu-sub-accordion">
                            @can('ptkp-sync')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('ptkp.sync.dashboard') ? 'active' : '' }}" 
                                   href="{{ route('ptkp.sync.dashboard') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">PTKP</span>
                                </a>
                            </div>
                            @endcan

                            @can('ptkp-history-sync')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('ptkp.history.sync.dashboard') ? 'active' : '' }}" 
                                   href="{{ route('ptkp.history.sync.dashboard') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">PTKP History</span>
                                </a>
                            </div>
                            @endcan

                            @can('periode-karyawan')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('periode-karyawan.*') ? 'active' : '' }}" 
                                   href="{{ route('periode-karyawan.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Periode Karyawan</span>
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                    @endif

                    @can('company-sync')
                    <div class="menu-item">
                        <a class="menu-link" href="{{ route('companies.sync.dashboard') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-office-bag fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Company</span>
                        </a>
                    </div>
                    @endcan

                    {{-- ============================================
                        PAYROLL MENU
                    ============================================ --}}
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('payrolls.*') ? 'active' : '' }}" 
                        href="{{ route('payrolls.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-wallet fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Payroll</span>
                        </a>
                    </div>

                    {{-- ============================================
                        DIVIDER & LOGOUT
                    ============================================ --}}
                    <div class="menu-item">
                        <div class="menu-content">
                            <div class="separator mx-1 my-4"></div>
                        </div>
                    </div>

                    {{-- ============================================
                        LOGOUT MENU
                    ============================================ --}}
                    <div class="menu-item">
                        <a class="menu-link" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-exit-right fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Logout</span>
                        </a>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>