{{-- resources/views/dashboard/dashboard-admin/range-bruto/sync.blade.php --}}

@extends('layouts.master')

@section('title', 'Sinkronisasi Range Bruto')

@section('content')
<div class="container-fluid">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>💰 Sinkronisasi Range Bruto</h2>
        <div>
            <button type="button" class="btn btn-secondary" onclick="refreshStats()">
                <i class="fas fa-sync"></i> Refresh Stats
            </button>
        </div>
    </div>
    
    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    
    {{-- Health Status --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status Kesehatan Sistem</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                @if($health['status'] === 'healthy')
                                    <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Status: <span class="badge bg-success">HEALTHY</span></h6>
                                        <small class="text-muted">{{ $health['message'] }}</small>
                                    </div>
                                @elseif($health['status'] === 'warning')
                                    <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Status: <span class="badge bg-warning">WARNING</span></h6>
                                        <small class="text-muted">{{ $health['message'] }}</small>
                                    </div>
                                @else
                                    <i class="fas fa-times-circle text-danger fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Status: <span class="badge bg-danger">CRITICAL</span></h6>
                                        <small class="text-muted">{{ $health['message'] }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar 
                                    @if($health['health_percentage'] >= 90) bg-success
                                    @elseif($health['health_percentage'] >= 70) bg-warning
                                    @else bg-danger
                                    @endif" 
                                    role="progressbar" 
                                    style="width: {{ $health['health_percentage'] }}%">
                                    {{ $health['health_percentage'] }}%
                                </div>
                            </div>
                            <small class="text-muted">Health Score</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-list-ol fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['total']) }}</h3>
                    <p class="text-muted mb-0">Total Range Bruto</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['active']) }}</h3>
                    <p class="text-muted mb-0">Active</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['deleted']) }}</h3>
                    <p class="text-muted mb-0">Deleted</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-sync-alt fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['needs_sync']) }}</h3>
                    <p class="text-muted mb-0">Needs Sync</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- TER Statistics & Sync Information --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-line text-purple"></i> TER Statistics
                    </h5>
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-chart-bar text-purple"></i> Average TER</td>
                            <td class="text-end">
                                <span class="badge bg-purple">{{ $stats['ter_stats']['avg'] }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-arrow-down text-success"></i> Minimum TER</td>
                            <td class="text-end">
                                <span class="badge bg-success">{{ $stats['ter_stats']['min'] }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-arrow-up text-danger"></i> Maximum TER</td>
                            <td class="text-end">
                                <span class="badge bg-danger">{{ $stats['ter_stats']['max'] }}%</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Informasi Sync Terakhir</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-clock text-primary"></i> Last Sync</td>
                            <td class="text-end">
                                @if($stats['last_sync'])
                                    <strong>{{ $stats['last_sync_human'] }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $stats['last_sync'] }}</small>
                                @else
                                    <span class="badge bg-secondary">Never Synced</span>
                                @endif
                            </td>
                        </tr>
                        @if($stats['never_synced'] > 0)
                        <tr>
                            <td><i class="fas fa-exclamation-triangle text-warning"></i> Never Synced</td>
                            <td class="text-end">
                                <span class="badge bg-warning">{{ number_format($stats['never_synced']) }} items</span>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Sync Actions --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Manual Sinkronisasi</h5>
                    <p class="text-muted">
                        Klik tombol di bawah untuk menjalankan sinkronisasi manual Range Bruto. 
                        Proses ini akan mengambil data terbaru dari Aplikasi Absen.
                    </p>
                    
                    <form action="{{ route('range-bruto.sync.trigger') }}" method="POST" id="syncForm" onsubmit="return confirmSync()">
                        @csrf
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="force" id="forceSync" value="1">
                                <label class="form-check-label" for="forceSync">
                                    <strong>Force Refresh</strong> - Abaikan cache dan ambil data fresh dari API
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary btn-lg" id="syncButton">
                                <i class="fas fa-sync"></i> Jalankan Sinkronisasi Sekarang
                            </button>
                            
                            <a href="{{ route('range-bruto.sync.dashboard') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-redo"></i> Refresh Page
                            </a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Pastikan <strong>Jenis TER</strong> sudah di-sync terlebih dahulu</li>
                            <li>Proses sinkronisasi dapat memakan waktu beberapa menit</li>
                            <li>Jangan refresh atau tutup halaman selama proses berlangsung</li>
                            <li>Data yang sudah ada akan di-update otomatis jika ada perubahan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- DataTable Section --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            📋 Daftar Range Bruto
                        </h5>
                        
                        {{-- Filter by Jenis TER --}}
                        <div class="d-flex align-items-center gap-2">
                            <label class="text-muted mb-0">Filter by Jenis TER:</label>
                            <select id="jenisTerFilter" class="form-select form-select-sm" style="width: 200px;">
                                <option value="">All Jenis TER</option>
                                @foreach($jenisTers as $jenisTer)
                                <option value="{{ $jenisTer->id }}">{{ $jenisTer->jenis_ter }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="rangeBrutoTable" class="table table-hover table-bordered" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>Absen ID</th>
                                    <th>Jenis TER</th>
                                    <th>Min Bruto</th>
                                    <th>Max Bruto</th>
                                    <th>TER (%)</th>
                                    <th>Last Synced</th>
                                    <th>Created At</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

@endsection

@push('scripts')
<script>
let table;

function confirmSync() {
    const force = document.getElementById('forceSync').checked;
    const message = force 
        ? 'Anda yakin ingin menjalankan FORCE SYNC? Ini akan mengabaikan cache dan memakan waktu lebih lama.'
        : 'Anda yakin ingin menjalankan sinkronisasi sekarang?';
    
    if (confirm(message)) {
        const btn = document.getElementById('syncButton');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Sinkronisasi...';
        return true;
    }
    return false;
}

function refreshStats() {
    fetch('{{ route("range-bruto.sync.status") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Gagal refresh stats');
        });
}

// Initialize DataTables
$(document).ready(function() {
    table = $('#rangeBrutoTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('range-bruto.sync.datatable') }}',
            type: 'POST',
            data: function(d) {
                d.jenis_ter_id = $('#jenisTerFilter').val();
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            error: function(xhr, error, code) {
                console.error('DataTables Error:', xhr.responseText);
            }
        },
        columns: [
            { data: 'absen_range_bruto_id', name: 'absen_range_bruto_id', width: '100px' },
            { data: 'jenis_ter', name: 'jenis_ter', width: '150px' },
            { data: 'min_bruto', name: 'min_bruto', width: '120px' },
            { data: 'max_bruto', name: 'max_bruto', width: '120px' },
            { data: 'ter', name: 'ter', width: '80px' },
            { data: 'last_synced_at', name: 'last_synced_at', width: '150px' },
            { data: 'created_at', name: 'created_at', width: '150px' },
            { 
                data: 'status_badge', 
                name: 'status_badge',
                className: 'text-center',
                orderable: false,
                searchable: false,
                width: '100px'
            }
        ],
        order: [[2, 'asc']], // Order by min_bruto
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ Range Bruto',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total Range Bruto)',
            zeroRecords: 'Tidak ada data yang cocok',
            emptyTable: 'Tidak ada Range Bruto tersedia',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        }
    });
    
    // Filter by Jenis TER
    $('#jenisTerFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush