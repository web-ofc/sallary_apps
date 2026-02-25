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
                    @if(auth()->id() === 1)
                    <div class="menu-item">
                        <div class="menu-content pt-8 pb-2">
                            <span class="menu-section text-muted text-uppercase fs-8 ls-1">Management</span>
                        </div>
                    </div>

                    {{-- ============================================
                        USER MANAGEMENT
                    ============================================ --}}
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('manage-user.*') ? 'active' : '' }}" 
                           href="{{ route('manage-user.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-people fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                            <span class="menu-title">User Management</span>
                        </a>
                    </div>
                    @endif

                    {{-- ============================================
                        COMPANY
                    ============================================ --}}
                    @can('company-sync')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('companies.sync.*') ? 'active' : '' }}" 
                            href="{{ route('companies.sync.dashboard') }}">
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
                        A1
                    ============================================ --}}
                    @if(auth()->id() === 1)
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('reimbursement-files.*') ? 'active' : '' }}" 
                            href="{{ route('reimbursement-files.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-office-bag fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">A1 File</span>
                        </a>
                    </div>
                    @endif
                    
                    {{-- ============================================
                    MUTASI COMPANY
                    ============================================ --}}
                    @can('manage-mutasicompany')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('manage-mutasicompany.*') ? 'active' : '' }}" 
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
                    @endcan

                    {{-- ============================================
                        DATA KARYAWAN GROUP (ACCORDION)
                    ============================================ --}}
                    @if(Gate::check('karyawan-sync') || Gate::check('ptkp-sync') || Gate::check('ptkp-history-sync') || Gate::check('periode-karyawan') || Gate::check('jenis-ter-sync') || Gate::check('range-bruto-sync') || Gate::check('manage-setting-admin-user')) 
                    <div data-kt-menu-trigger="click" 
                         class="menu-item menu-accordion {{ request()->routeIs('karyawan.*') || request()->routeIs('ptkp.*') || request()->routeIs('periode-karyawan.*') || request()->routeIs('jenis-ter.*') || request()->routeIs('manage-setting-admin-user.index') || request()->routeIs('range-bruto.*') ? 'here show' : '' }}">
                        
                        {{-- Parent Menu --}}
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-profile-user fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Data Karyawan</span>
                            <span class="menu-arrow"></span>
                        </span>

                        {{-- Submenu --}}
                        <div class="menu-sub menu-sub-accordion">
                            
                            {{-- Karyawan --}}
                            @can('karyawan-sync')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('karyawan.sync.*') ? 'active' : '' }}" 
                                   href="{{ route('karyawan.sync.dashboard') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Karyawan</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Periode Karyawan --}}
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

                            {{-- PTKP --}}
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

                            {{-- PTKP History --}}
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

                            {{-- Jenis TER --}}
                            @can('jenis-ter-sync')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('jenis-ter.sync.*') ? 'active' : '' }}" 
                                   href="{{ route('jenis-ter.sync.dashboard') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Jenis TER</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Range Bruto --}}
                            @can('range-bruto-sync')
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('range-bruto.sync.*') ? 'active' : '' }}" 
                                   href="{{ route('range-bruto.sync.dashboard') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Range Bruto</span>
                                </a>
                            </div>
                            @endcan

                            {{-- Range Bruto --}}
                            @if(auth()->id() === 1)
                            <div class="menu-item">
                                <a class="menu-link {{ request()->routeIs('manage-setting-admin-user.index') ? 'active' : '' }}" 
                                   href="{{ route('manage-setting-admin-user.index') }}">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot"></span>
                                    </span>
                                    <span class="menu-title">Setting User</span>
                                </a>
                            </div>
                            @endif

                        </div>
                    </div>
                    @endif

                    {{-- ============================================
                        DATA REIMBURSTMENT GROUP (ACCORDION)
                    ============================================ --}}
                    @auth
                        @if(in_array(auth()->id(), [1, 14]))
                        <div data-kt-menu-trigger="click" 
                            class="menu-item menu-accordion 
                            {{  
                            request()->routeIs('manage-reimbursements.*') || 
                            request()->routeIs('master-salaries.*') || 
                            request()->routeIs('balance-reimbursements.*') || 
                            request()->routeIs('manage-reimbursementperiods.*') ? 'here show' : '' 
                            }}">
                            
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-duotone ki-profile-user fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </span>
                                <span class="menu-title">Medical Reimbursement</span>
                                <span class="menu-arrow"></span>
                            </span>

                            <div class="menu-sub menu-sub-accordion">
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('manage-reimbursements.*') ? 'active' : '' }}" 
                                    href="{{ route('manage-reimbursements.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Reimbursement</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('manage-reimbursementperiods.*') ? 'active' : '' }}" 
                                    href="{{ route('manage-reimbursementperiods.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Periode</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('master-salaries.*') ? 'active' : '' }}" 
                                    href="{{ route('master-salaries.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Master Salary</span>
                                    </a>
                                </div>
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->routeIs('balance-reimbursements.*') ? 'active' : '' }}" 
                                    href="{{ route('balance-reimbursements.index') }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title">Balance Reimbursements</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endauth

                    {{-- ============================================
                        DIVIDER
                    ============================================ --}}
                    @can('manage-payroll')
                    <div class="menu-item">
                        <div class="menu-content pt-8 pb-2">
                            <span class="menu-section text-muted text-uppercase fs-8 ls-1">Payroll & Tax</span>
                        </div>
                    </div>
                    
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
                            <span class="menu-title">Payroll Masa</span>
                        </a>
                    </div>
                    @endcan
                    {{-- ============================================
                        PAYROLL MENU
                    ============================================ --}}
                    @can('manage-payroll-fake')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('payrolls-fake.*') ? 'active' : '' }}" 
                           href="{{ route('payrolls-fake.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-wallet fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">Payroll Akhir</span>
                        </a>
                    </div>
                    @endcan
                    {{-- ============================================
                        PPH21 BRACKET
                    ============================================ --}}
                    @can('pph21-tax-brackets')
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('pph21taxbrackets.*') ? 'active' : '' }}" 
                            href="{{ route('pph21taxbrackets.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-chart-simple-3 fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                    <span class="path4"></span>
                                </i>
                            </span>
                            <span class="menu-title">PPH21 Bracket</span>
                        </a>
                    </div>
                    @endcan
                    
                    {{-- ============================================
                    DIVIDER
                    ============================================ --}}
                    @can('pph21-tahunan')
                    <div class="menu-item">
                        <div class="menu-content pt-8 pb-2">
                            <span class="menu-section text-muted text-uppercase fs-8 ls-1">Reports</span>
                        </div>
                    </div>
                    
                    {{-- ============================================
                    LAPORAN PPH21 TAHUNAN
                    ============================================ --}}
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('pph21.tahunan.*') ? 'active' : '' }}" 
                            href="{{ route('pph21.tahunan.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-document fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">PPH21 Tahunan</span>
                        </a>
                    </div>
                    @endcan
                    
                    @if(auth()->id() === 1)
                    <div class="menu-item">
                        <a class="menu-link {{ request()->routeIs('manage-pph21companyperiode.*') ? 'active' : '' }}" 
                            href="{{ route('manage-pph21companyperiode.index') }}">
                            <span class="menu-icon">
                                <i class="ki-duotone ki-document fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                            <span class="menu-title">Company Periode PPH21</span>
                        </a>
                    </div>
                    @endif

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