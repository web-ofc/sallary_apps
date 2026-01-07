{{-- resources/views/dashboard/dashboard-admin/ptkp-history/sync.blade.php --}}

@extends('layouts.master')

@section('title', 'Sinkronisasi PTKP History')

@section('content')
<div class="container-fluid">
    
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Sinkronisasi PTKP History Karyawan</h2>
        <div>
            <button type="button" class="btn btn-primary" onclick="refreshStats()">
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
    
    {{-- Sync Health Status --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Status Sinkronisasi</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                @if($health['healthy'])
                                    <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Status: <span class="badge bg-success">HEALTHY</span></h6>
                                        <small class="text-muted">Semua data ter-sinkronisasi dengan baik</small>
                                    </div>
                                @else
                                    <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                                    <div>
                                        <h6 class="mb-0">Status: <span class="badge bg-warning">NEEDS SYNC</span></h6>
                                        <small class="text-muted">{{ $health['needs_sync_count'] }} history perlu di-sync</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar 
                                    @if($health['percentage_synced'] >= 90) bg-success
                                    @elseif($health['percentage_synced'] >= 70) bg-warning
                                    @else bg-danger
                                    @endif" 
                                    role="progressbar" 
                                    style="width: {{ $health['percentage_synced'] }}%">
                                    {{ $health['percentage_synced'] }}%
                                </div>
                            </div>
                            <small class="text-muted">Sync Coverage</small>
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
                    <i class="fas fa-history fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['total_histories']) }}</h3>
                    <p class="text-muted mb-0">Total History</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-info mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['total_karyawan']) }}</h3>
                    <p class="text-muted mb-0">Karyawan Unik</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['soft_deleted']) }}</h3>
                    <p class="text-muted mb-0">Soft Deleted</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">{{ number_format($stats['never_synced']) }}</h3>
                    <p class="text-muted mb-0">Never Synced</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Year Statistics --}}
    @if(!empty($stats['by_year']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Statistik per Tahun</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tahun</th>
                                    <th class="text-end">Total History</th>
                                    <th class="text-end">Karyawan Unik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['by_year'] as $year => $data)
                                <tr>
                                    <td><strong>{{ $year }}</strong></td>
                                    <td class="text-end">{{ number_format($data['count']) }}</td>
                                    <td class="text-end">{{ number_format($data['unique_karyawan']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Sync Information --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Informasi Sync Terakhir</h5>
                    <table class="table table-sm">
                        <tr>
                            <td><i class="fas fa-clock text-primary"></i> Last Sync</td>
                            <td class="text-end">
                                @if($stats['last_sync_time'])
                                    <strong>{{ \Carbon\Carbon::parse($stats['last_sync_time'])->diffForHumans() }}</strong>
                                    <br>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($stats['last_sync_time'])->format('d M Y H:i') }}</small>
                                @else
                                    <span class="badge bg-secondary">Never Synced</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-history text-warning"></i> Oldest Sync</td>
                            <td class="text-end">
                                @if($stats['oldest_sync_time'])
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($stats['oldest_sync_time'])->format('d M Y H:i') }}</small>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Manual Sinkronisasi</h5>
                    <p class="text-muted">
                        Klik tombol di bawah untuk menjalankan sinkronisasi manual PTKP History. 
                        Proses ini akan mengambil data terbaru dari aplikasi ABSEN.
                    </p>
                    
                    <form action="{{ route('ptkp.history.sync.trigger') }}" method="POST" id="syncForm" onsubmit="return confirmSync()">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Filter Tahun (Opsional)</label>
                            <select class="form-select" name="tahun" id="filterTahun">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan untuk sync semua tahun</small>
                        </div>
                        
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
                            
                            <a href="{{ route('ptkp.history.sync.dashboard') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-redo"></i> Refresh Page
                            </a>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Proses sinkronisasi dapat memakan waktu beberapa menit</li>
                            <li>Jangan refresh atau tutup halaman selama proses berlangsung</li>
                            <li>Data yang sudah ada akan di-update otomatis jika ada perubahan</li>
                            <li>History yang tidak ada di API akan di-soft delete</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- SECTION: PTKP HISTORY LIST (DATATABLES) --}}
    {{-- ========================================== --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                       📋 Daftar PTKP History Karyawan
                    </h5>
                </div>
                <div class="card-body">
                    {{-- Filter Tahun untuk DataTable --}}
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Filter Tahun:</label>
                            <select class="form-select" id="dtFilterTahun">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="ptkpHistoryTable" class="table table-hover table-bordered" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>ID History</th>
                                    <th>Karyawan</th>
                                    <th>PTKP</th>
                                    <th>Tahun</th>
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
function confirmSync() {
    const force = document.getElementById('forceSync').checked;
    const tahun = document.getElementById('filterTahun').value;
    
    let message = 'Anda yakin ingin menjalankan sinkronisasi sekarang?';
    
    if (tahun) {
        message = `Anda yakin ingin sync data tahun ${tahun}?`;
    }
    
    if (force) {
        message += '\n\nFORCE REFRESH aktif - ini akan mengabaikan cache dan memakan waktu lebih lama.';
    }
    
    if (confirm(message)) {
        const btn = document.getElementById('syncButton');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sedang Sinkronisasi...';
        return true;
    }
    return false;
}

function refreshStats() {
    fetch('{{ route("ptkp.history.sync.status") }}')
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
    const table = $('#ptkpHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('ptkp.history.sync.datatable') }}',
            type: 'POST',
            data: function(d) {
                d.tahun = $('#dtFilterTahun').val();
                return d;
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            error: function(xhr, error, code) {
                console.error('DataTables Error:', xhr.responseText);
            }
        },
        columns: [
            { data: 'absen_ptkp_history_id', name: 'absen_ptkp_history_id', width: '100px' },
            { data: 'karyawan_nama', name: 'karyawan_nama', width: '200px' },
            { data: 'ptkp_kriteria', name: 'ptkp_kriteria', width: '150px' },
            { data: 'tahun', name: 'tahun', width: '80px' },
            { data: 'last_synced_at', name: 'last_synced_at', width: '120px' },
            { data: 'created_at', name: 'created_at', width: '120px' },
            { 
                data: 'status_badge', 
                name: 'status_badge',
                className: 'text-center',
                orderable: false,
                searchable: false,
                width: '80px'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ history',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(difilter dari _MAX_ total history)',
            zeroRecords: 'Tidak ada data yang cocok',
            emptyTable: 'Tidak ada PTKP History tersedia',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        }
    });
    
    // Reload DataTable when filter changes
    $('#dtFilterTahun').on('change', function() {
        table.ajax.reload();
    });
});
</script>
@endpush