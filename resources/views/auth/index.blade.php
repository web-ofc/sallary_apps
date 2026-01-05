<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->
@include('layouts._head')
<!--end::Head-->

<!--begin::Body-->
<body id="kt_body" class="app-blank">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light"; 
        var themeMode; 
        if (document.documentElement) { 
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) { 
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); 
            } else { 
                if (localStorage.getItem("data-bs-theme") !== null) { 
                    themeMode = localStorage.getItem("data-bs-theme"); 
                } else { 
                    themeMode = defaultThemeMode; 
                } 
            } 
            if (themeMode === "system") { 
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; 
            } 
            document.documentElement.setAttribute("data-bs-theme", themeMode); 
        }
    </script>
    <!--end::Theme mode setup on page load-->

    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-center flex-column-fluid p-10">
            <!--begin::Wrapper-->
            <div class="w-lg-500px w-100 p-10 bg-white rounded-4 shadow-sm">
               

                <!--begin::Form-->
                <form class="form w-100" novalidate="novalidate" action="/" method="POST" id="loginForm">
                    @csrf
                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <!--begin::Heading-->
                    <div class="text-center mb-10">
                        <h1 class="fw-bolder mb-2" style="font-size: 1.75rem; color: #1a202c;">Sign In</h1>
                        <div class="fw-semibold fs-6" style="color: #64748b;">
                            Masuk ke akun Anda untuk melanjutkan
                        </div>
                    </div>
                    <!--end::Heading-->

                    <!--begin::Alert Messages-->
                    @if(session()->has('loginError'))
                        <div class="alert alert-dismissible bg-light-danger d-flex flex-column flex-sm-row p-5 mb-8">
                            <span class="svg-icon svg-icon-2hx svg-icon-danger me-4 mb-5 mb-sm-0">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"/>
                                    <rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="currentColor"/>
                                    <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="currentColor"/>
                                </svg>
                            </span>
                            <div class="d-flex flex-column text-danger pe-0 pe-sm-10">
                                <h5 class="fw-semibold mb-1">Login Gagal</h5>
                                <span>{{ session('loginError') }}</span>
                            </div>
                            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                <span class="svg-icon svg-icon-1 svg-icon-danger">×</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->has('TokenMismatchException') || session('error') == 'CSRF token mismatch.')
                        <div class="alert alert-dismissible bg-light-warning d-flex flex-column flex-sm-row p-5 mb-8">
                            <span class="svg-icon svg-icon-2hx svg-icon-warning me-4 mb-5 mb-sm-0">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"/>
                                    <rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="currentColor"/>
                                    <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="currentColor"/>
                                </svg>
                            </span>
                            <div class="d-flex flex-column text-warning pe-0 pe-sm-10">
                                <h5 class="fw-semibold mb-1">⏰ Sesi Berakhir!</h5>
                                <span>Halaman terlalu lama tidak digunakan. Silakan coba login lagi.</span>
                            </div>
                            <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                <span class="svg-icon svg-icon-1 svg-icon-warning">×</span>
                            </button>
                        </div>
                    @endif
                    <!--end::Alert Messages-->

                    <!--begin::Input group - Email-->
                    <div class="fv-row mb-7">
                        <label class="form-label fs-6 fw-bold" style="color: #334155;">Email</label>
                        <div class="position-relative">
                            <span class="svg-icon svg-icon-2 position-absolute translate-middle top-50 ms-6" style="color: #94a3b8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path opacity="0.3" d="M21 19H3C2.4 19 2 18.6 2 18V6C2 5.4 2.4 5 3 5H21C21.6 5 22 5.4 22 6V18C22 18.6 21.6 19 21 19Z" fill="currentColor"/>
                                    <path d="M21 5H2.99999C2.69999 5 2.49999 5.10005 2.29999 5.30005L11.2 13.3C11.7 13.7 12.4 13.7 12.8 13.3L21.7 5.30005C21.5 5.10005 21.3 5 21 5Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <input type="email" placeholder="name@company.com" name="email" value="{{ old('email') }}" 
                                   autocomplete="email"
                                   class="form-control form-control-lg form-control-modern ps-14 @error('email') is-invalid @enderror" 
                                   required />
                            @error('email')
                                <div class="invalid-feedback px-3 py-2 rounded mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group - Password-->
                    <div class="fv-row mb-5">
                        <label class="form-label fw-bold fs-6 mb-2" style="color: #334155;">Password</label>
                        <div class="position-relative">
                            <span class="svg-icon svg-icon-2 position-absolute translate-middle top-50 ms-6" style="color: #94a3b8;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M18 8H17V7C17 4.24 14.76 2 12 2C9.24 2 7 4.24 7 7V8H6C4.9 8 4 8.9 4 10V20C4 21.1 4.9 22 6 22H18C19.1 22 20 21.1 20 20V10C20 8.9 19.1 8 18 8ZM12 17C10.9 17 10 16.1 10 15C10 13.9 10.9 13 12 13C13.1 13 14 13.9 14 15C14 16.1 13.1 17 12 17ZM9 8V7C9 5.34 10.34 4 12 4C13.66 4 15 5.34 15 7V8H9Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <input type="password" placeholder="••••••••" name="password" id="passwordInput" 
                                   autocomplete="current-password"
                                   class="form-control form-control-lg form-control-modern ps-14 pe-14 @error('password') is-invalid @enderror" 
                                   required />
                            <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-3" id="togglePassword">
                                <span class="svg-icon svg-icon-2" id="eyeIcon" style="color: #94a3b8;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5C5.63636 5 2 12 2 12C2 12 5.63636 19 12 19C18.3636 19 22 12 22 12C22 12 18.3636 5 12 5Z" fill="currentColor"/>
                                        <path d="M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" fill="white"/>
                                    </svg>
                                </span>
                            </span>
                            @error('password')
                                <div class="invalid-feedback px-3 py-2 rounded mt-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <!--end::Input group-->

                    <!--begin::Wrapper-->
                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div></div>
                        <a href="" class="link-modern fw-bold">Forgot Password?</a>
                    </div>
                    <!--end::Wrapper-->

                    <!--begin::Submit button-->
                    <div class="d-grid mb-8">
                        <button type="submit" class="btn btn-lg btn-modern" id="submitBtn">
                            <span class="indicator-label">Sign In</span>
                            <span class="indicator-progress" style="display: none;">
                                Please wait... 
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    <!--end::Submit button-->
                </form>
                <!--end::Form-->

                <!--begin::Footer-->
                <div class="text-center">
                    <div class="fw-semibold fs-7" style="color: #94a3b8;">
                        <span>© 2026 Payroll System.</span>
                        <a href="#" class="text-hover-primary px-2" style="color: #64748b;">About</a>
                        <a href="#" class="text-hover-primary px-2" style="color: #64748b;">Support</a>
                    </div>
                </div>
                <!--end::Footer-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Root-->

    <!--begin::Javascript-->
    @include('layouts._scripts')
    
    <script>
        // Toggle Password Visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('passwordInput');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });

        // Form Submit Handler
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const label = submitBtn.querySelector('.indicator-label');
            const progress = submitBtn.querySelector('.indicator-progress');
            
            // Show loading
            label.style.display = 'none';
            progress.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            // Reset after 10 seconds in case of error
            setTimeout(() => {
                label.style.display = 'inline-block';
                progress.style.display = 'none';
                submitBtn.disabled = false;
            }, 10000);
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

    <script src="{{ asset('pwa-install.js') }}"></script>
    <!--end::Javascript-->
</body>
<!--end::Body-->
</html>

<style>
/* Modern Soft White Background */
body { 
    background: linear-gradient(135deg, #fafbfc 0%, #f5f7fa 100%) !important;
    min-height: 100vh;
}

[data-bs-theme="dark"] body { 
    background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%) !important;
}

/* Card Shadow */
.shadow-sm {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
}

/* Input Fields - Modern Clean Style */
.form-control-modern {
    background-color: #f8fafc !important;
    border: 1.5px solid #e2e8f0 !important;
    color: #1e293b !important;
    transition: all 0.2s ease;
    font-size: 0.95rem;
}

.form-control-modern:focus {
    background-color: #ffffff !important;
    border-color: #667eea !important;
    color: #1e293b !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

.form-control-modern::placeholder {
    color: #94a3b8 !important;
}

/* Button Modern Style */
.btn-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: #ffffff !important;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-modern:hover {
    background: linear-gradient(135deg, #5568d3 0%, #63408a 100%) !important;
    color: #ffffff !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

/* Link Style */
.link-modern {
    color: #667eea !important;
    text-decoration: none;
}

.link-modern:hover {
    color: #5568d3 !important;
    text-decoration: underline;
}

/* Alert Styles - Modern Clean */
.alert {
    border-radius: 12px;
    border: none;
    animation: slideDown 0.3s ease-out;
}

.bg-light-danger {
    background-color: #fef2f2 !important;
}

.text-danger {
    color: #dc2626 !important;
}

.svg-icon-danger {
    color: #dc2626 !important;
}

.bg-light-warning {
    background-color: #fffbeb !important;
}

.text-warning {
    color: #d97706 !important;
}

.svg-icon-warning {
    color: #d97706 !important;
}

/* Password Toggle Button */
#togglePassword {
    cursor: pointer;
    background: transparent;
    border: none;
    transition: all 0.3s ease;
}

#togglePassword:hover {
    background: rgba(102, 126, 234, 0.1);
    border-radius: 8px;
}

/* Invalid Feedback */
.invalid-feedback {
    background-color: #fef2f2 !important;
    color: #dc2626 !important;
    font-size: 0.875rem;
    display: block !important;
}

/* Smooth Animation */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loading Spinner */
.spinner-border {
    animation: spinner-border 0.75s linear infinite;
}

@keyframes spinner-border {
    to {
        transform: rotate(360deg);
    }
}

/* Smooth Transitions */
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Form Label */
.form-label {
    margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 576px) {
    .w-lg-500px {
        padding: 2rem 1.5rem !important;
    }
}
</style>