<div class="d-flex align-items-center">
    <div class="d-flex flex-column">
        <span class="text-gray-800 text-hover-primary mb-1 fw-bold">
            {{ $row->karyawan->nama_lengkap ?? '-' }}
        </span>
        <span class="text-muted fs-7">
            NIK: {{ $row->karyawan->nik ?? '-' }}
        </span>
    </div>
</div>
