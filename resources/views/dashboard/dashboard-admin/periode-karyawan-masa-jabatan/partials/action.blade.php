<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('periode-karyawan.show', [$row->periode, $row->karyawan_id, $row->company_id, $row->salary_type]) }}" 
       class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
        <i class="bi bi-eye fs-4"></i>
    </a>
</div>