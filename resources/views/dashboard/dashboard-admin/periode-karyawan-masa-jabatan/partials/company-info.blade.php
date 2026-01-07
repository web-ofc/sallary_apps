<div class="d-flex align-items-center">
    <div class="d-flex flex-column">
        <span class="text-gray-800 mb-1 fw-bold">
            {{ $row->company->company_name ?? '-' }}
        </span>
        <span class="text-muted fs-7">
            Code: {{ $row->company->code ?? '-' }}
        </span>
    </div>
</div>