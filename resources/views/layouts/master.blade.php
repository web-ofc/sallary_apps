<!DOCTYPE html>
<html lang="id">
    <head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@include('layouts._head')

<body id="kt_app_body" data-kt-app-layout="light-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            
            @include('layouts.topbar')

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                
                @include('layouts.sidebar')

                @yield('no-container')
                <div class="app-main flex-column flex-row-fluid m-5" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        
                        @yield('content')

                    </div>
                </div>
            </div>
        </div>
    </div>
     <!-- ✅ Setup AJAX Headers -->
    @include('layouts._scripts')

</body>


</html>