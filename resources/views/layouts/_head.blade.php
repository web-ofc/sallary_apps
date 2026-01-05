<head>
    {{-- Tambahkan baris ini --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Haadhir Apps</title>
    
    <meta charset="utf-8" />
    <meta name="description" content="Haadhir.id adalah aplikasi absensi modern dengan teknologi face recognition. Memudahkan pencatatan kehadiran karyawan secara akurat, cepat, dan aman." />
    <meta name="keywords" content="haadhir.id, absensi online, face recognition, aplikasi absensi, presensi wajah, kehadiran karyawan, sistem absensi, absen digital, attendance app, absensi kantor" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Haadhir.id — Aplikasi Absensi dengan Face Recognition" />
    <meta name="theme-color" content="#009ef7">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Haadhir">
    <meta name="mobile-web-app-capable" content="yes">

      <!-- ✅ CSRF Token Meta Tag -->
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="{{ asset('assets/media/Haadhir.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/media/Haadhir.png') }}">
    
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:site_name" content="{{ config('app.name') }}" />

    <link rel="shortcut icon" href="{{ asset('assets/media/Haadhir.png') }}" />
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

    
    @stack('css')

</head>