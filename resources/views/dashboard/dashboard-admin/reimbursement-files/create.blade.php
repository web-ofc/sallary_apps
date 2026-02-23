{{-- resources/views/dashboard/dashboard-admin/reimbursement-files/create.blade.php --}}

@extends('layouts.master')

@section('styles')
<link href="{{ asset('metronic/assets/plugins/custom/dropzone/dropzone.bundle.css') }}" rel="stylesheet" type="text/css" />
<style>
.dropzone {
    border: 2px dashed #E4E6EF;
    border-radius: 0.625rem;
    background: #F9F9F9;
    min-height: 150px;
    padding: 20px;
}

.dropzone.dz-drag-hover {
    border-color: #009ef7;
    background: #f1faff;
}

.dropzone .dz-message {
    margin: 2em 0;
}

.card-karyawan.has-error {
    border: 2px solid #f1416c !important;
    background-color: #fff5f8;
}

.card-karyawan.has-success {
    border: 2px solid #50cd89 !important;
    background-color: #f1faff;
}

.error-badge {
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}

.file-count-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}
</style>
@endsection

@section('content')
<div class="container-xxl">
    <form id="form_upload_files" class="form">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">

        {{-- Header Card --}}
        <div class="card mb-5 shadow-sm">
            <div class="card-header bg-light">
                <div class="card-title">
                    <h3 class="fw-bold m-0">
                        <i class="ki-duotone ki-cloud-upload fs-2 text-primary me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Upload A1 Files - Year {{ $year }}
                    </h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('reimbursement-files.index') }}" class="btn btn-light btn-sm me-3">
                        <i class="ki-duotone ki-arrow-left fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Back
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn_submit">
                        <span class="indicator-label">
                            <i class="ki-duotone ki-check fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Submit All Files
                        </span>
                        <span class="indicator-progress">
                            Uploading...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>

            {{-- ✅ Global Error Alert --}}
            <div id="global_error_alert" class="alert alert-danger d-none m-5" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ki-duotone ki-information-5 fs-2x text-danger me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="flex-grow-1">
                        <h4 class="mb-1 text-danger">Upload Error!</h4>
                        <div id="global_error_content" class="fw-semibold"></div>
                    </div>
                </div>
            </div>

            {{-- ✅ Global Success Alert --}}
            <div id="global_success_alert" class="alert alert-success d-none m-5" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ki-duotone ki-check-circle fs-2x text-success me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <div class="flex-grow-1">
                        <h4 class="mb-1 text-success">Validation Passed!</h4>
                        <div class="fw-semibold">Semua file valid. Silakan klik "Submit All Files" untuk upload.</div>
                    </div>
                </div>
            </div>

            {{-- Upload Summary --}}
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center bg-light-primary rounded p-4">
                    <div>
                        <span class="text-gray-700 fw-bold">Total Karyawan:</span>
                        <span class="badge badge-primary ms-2">{{ $karyawans->count() }}</span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-bold">Total Files:</span>
                        <span class="badge badge-success ms-2" id="total_files_badge">0</span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-bold">Valid Files:</span>
                        <span class="badge badge-light-success ms-2" id="valid_files_badge">0</span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-bold">Invalid Files:</span>
                        <span class="badge badge-light-danger ms-2" id="invalid_files_badge">0</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loop Karyawan Cards --}}
        <div class="row g-5">
            @foreach($karyawans as $karyawan)
            <div class="col-md-6">
                <div class="card card-karyawan shadow-sm" id="card_{{ $karyawan->absen_karyawan_id }}" data-karyawan-id="{{ $karyawan->absen_karyawan_id }}">
                    {{-- Card Header --}}
                    <div class="card-header">
                        <div class="card-title">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-45px me-3">
                                    <div class="symbol-label bg-light-primary">
                                        <i class="ki-duotone ki-user fs-2x text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold fs-6">{{ $karyawan->nama_lengkap }}</span>
                                    <span class="text-muted fs-7">NIK: {{ $karyawan->nik ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary file-count-badge" id="file_count_{{ $karyawan->absen_karyawan_id }}">
                                0 files
                            </span>
                        </div>
                    </div>

                    {{-- Card Body --}}
                    <div class="card-body">
                        {{-- Dropzone --}}
                        <div class="dropzone" id="dropzone_{{ $karyawan->absen_karyawan_id }}">
                            <div class="dz-message needsclick">
                                <i class="ki-duotone ki-file-up fs-3x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <div class="ms-4">
                                    <h3 class="fs-5 fw-bold text-gray-900 mb-1">Drop files here or click to upload</h3>
                                    <span class="fs-7 fw-semibold text-gray-400">
                                        Max 5MB per file • JPEG, JPG, PNG, PDF
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Error Messages --}}
                        <div class="error-messages mt-3 d-none" id="errors_{{ $karyawan->absen_karyawan_id }}">
                            <div class="alert alert-danger mb-0">
                                <ul class="mb-0" id="error_list_{{ $karyawan->absen_karyawan_id }}"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script src="{{ asset('metronic/assets/plugins/custom/dropzone/dropzone.bundle.js') }}"></script>
<script>
"use strict";

let form_upload_files;
let btn_submit;
let dropzones = {};
let filesData = {}; // Store file data per karyawan
let validationStatus = {}; // Track validation per karyawan

$(document).ready(function() {
    form_upload_files = document.getElementById('form_upload_files');
    btn_submit = document.getElementById('btn_submit');

    // Initialize Dropzone for each karyawan
    @foreach($karyawans as $karyawan)
    initDropzone({{ $karyawan->absen_karyawan_id }}, "{{ $karyawan->nama_lengkap }}");
    @endforeach

    // Form submission
    $(form_upload_files).on('submit', function(e) {
        e.preventDefault();

        // Prevent double click
        if (btn_submit.disabled) {
            return false;
        }

        // Check if any files uploaded
        let totalFiles = Object.values(filesData).reduce((sum, files) => sum + files.length, 0);
        
        if (totalFiles === 0) {
            showGlobalError('Tidak ada file yang diupload! Silakan upload minimal 1 file.');
            return false;
        }

        // Check validation status
        let hasInvalidFiles = Object.values(validationStatus).some(status => status === false);
        
        if (hasInvalidFiles) {
            showGlobalError('Ada file yang tidak valid! Silakan perbaiki error sebelum submit.');
            return false;
        }

        // Disable button & show loading
        btn_submit.setAttribute('data-kt-indicator', 'on');
        btn_submit.disabled = true;

        // Prepare data
        let karyawanData = [];
        
        for (let karyawanId in filesData) {
            if (filesData[karyawanId].length > 0) {
                karyawanData.push({
                    karyawan_id: karyawanId,
                    files: filesData[karyawanId]
                });
            }
        }

        $.ajax({
            url: "{{ route('reimbursement-files.store') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                year: {{ $year }},
                karyawan_data: karyawanData
            },
            // Di bagian form submission success
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Upload Berhasil!',
                        text: response.message,
                        icon: 'success', // ✅ Pakai icon bawaan Swal aja
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(() => {
                        window.location.href = "{{ route('reimbursement-files.index') }}";
                    });
                }
            },
            error: function(xhr) {
                btn_submit.removeAttribute('data-kt-indicator');
                btn_submit.disabled = false;

                let errorMessage = 'Terjadi kesalahan saat upload';
                let errorList = [];
                
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors && Array.isArray(xhr.responseJSON.errors)) {
                        xhr.responseJSON.errors.forEach(function(error) {
                            errorList.push(error.karyawan_name + ': ' + error.message);
                        });
                        errorMessage = errorList.join('<br>');
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }

                showGlobalError(errorMessage);

                Swal.fire({
                    html: '<div class="text-start">' + errorMessage + '</div>',
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    },
                    width: '600px'
                });
            }
        });
    });
});

function initDropzone(karyawanId, karyawanName) {
    const dropzoneElement = document.querySelector("#dropzone_" + karyawanId);
    
    filesData[karyawanId] = [];
    validationStatus[karyawanId] = true;

    // ✅ FIX: Use template literal atau concat string biasa
    const myDropzone = new Dropzone(dropzoneElement, {
        url: "{{ route('reimbursement-files.store') }}", // Dummy URL
        paramName: "file",
        maxFilesize: 5, // MB
        acceptedFiles: "image/jpeg,image/jpg,image/png,application/pdf",
        addRemoveLinks: true,
        autoProcessQueue: false,
        parallelUploads: 10,
        dictDefaultMessage: "Drop files here or click to upload",
        dictFallbackMessage: "Your browser does not support drag and drop file uploads.",
        dictFileTooBig: "File terlalu besar. Maksimal 5MB.", // ✅ SIMPLIFIED
        dictInvalidFileType: "File type tidak valid. Hanya JPEG, JPG, PNG, PDF.", // ✅ SIMPLIFIED
        dictRemoveFile: "Hapus",
        dictCancelUpload: "Batal",
        
        init: function() {
            const dz = this;

            // ✅ On file added - validate immediately
            dz.on("addedfile", function(file) {
                console.log('File added:', file.name, 'Size:', file.size, 'Type:', file.type);

                // Check size BEFORE uploading (5MB = 5 * 1024 * 1024 bytes)
                if (file.size > 5 * 1024 * 1024) {
                    const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                    showKaryawanError(
                        karyawanId, 
                        karyawanName, 
                        'File "' + file.name + '" terlalu besar (' + fileSizeMB + 'MB). Maksimal 5MB'
                    );
                    dz.removeFile(file);
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    showKaryawanError(
                        karyawanId, 
                        karyawanName, 
                        'File "' + file.name + '" type tidak valid. Hanya JPEG, JPG, PNG, PDF'
                    );
                    dz.removeFile(file);
                    return;
                }

                // ✅ Convert to base64
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const base64Content = e.target.result.split(',')[1]; // Remove data:image/jpeg;base64,
                        
                        filesData[karyawanId].push({
                            name: file.name,
                            extension: file.name.split('.').pop().toLowerCase(),
                            content: base64Content,
                            size: file.size
                        });

                        console.log('File converted to base64:', file.name);
                        
                        updateFileCount(karyawanId);
                        updateGlobalStats();
                        clearKaryawanError(karyawanId);
                    } catch (error) {
                        console.error('Error converting file:', error);
                        showKaryawanError(karyawanId, karyawanName, 'Gagal memproses file "' + file.name + '"');
                        dz.removeFile(file);
                    }
                };
                
                reader.onerror = function(error) {
                    console.error('FileReader error:', error);
                    showKaryawanError(karyawanId, karyawanName, 'Gagal membaca file "' + file.name + '"');
                    dz.removeFile(file);
                };
                
                reader.readAsDataURL(file);
            });

            // ✅ On file removed
            dz.on("removedfile", function(file) {
                console.log('File removed:', file.name);
                
                const index = filesData[karyawanId].findIndex(f => f.name === file.name);
                if (index > -1) {
                    filesData[karyawanId].splice(index, 1);
                }
                
                updateFileCount(karyawanId);
                updateGlobalStats();
                
                if (filesData[karyawanId].length === 0) {
                    clearKaryawanError(karyawanId);
                }
            });

            // ✅ On error
            dz.on("error", function(file, message, xhr) {
                console.error('Dropzone error:', message);
                
                let errorMsg = message;
                if (typeof message === 'object' && message.message) {
                    errorMsg = message.message;
                } else if (typeof message === 'string') {
                    errorMsg = message;
                } else {
                    errorMsg = 'Error uploading file';
                }
                
                showKaryawanError(karyawanId, karyawanName, errorMsg);
                
                // Remove file from dropzone
                setTimeout(() => {
                    dz.removeFile(file);
                }, 2000);
            });

            // ✅ On max files exceeded
            dz.on("maxfilesexceeded", function(file) {
                console.warn('Max files exceeded');
                showKaryawanError(karyawanId, karyawanName, 'Terlalu banyak file');
                dz.removeFile(file);
            });
        }
    });

    dropzones[karyawanId] = myDropzone;
}

function updateFileCount(karyawanId) {
    const count = filesData[karyawanId].length;
    const badge = document.getElementById('file_count_' + karyawanId);
    
    if (badge) {
        badge.textContent = count + (count === 1 ? ' file' : ' files');
        
        if (count > 0) {
            badge.classList.remove('badge-light-primary');
            badge.classList.add('badge-success');
        } else {
            badge.classList.remove('badge-success');
            badge.classList.add('badge-light-primary');
        }
    }
}

function updateGlobalStats() {
    let totalFiles = 0;
    let validFiles = 0;
    let invalidFiles = 0;

    for (let karyawanId in filesData) {
        totalFiles += filesData[karyawanId].length;
        if (validationStatus[karyawanId]) {
            validFiles += filesData[karyawanId].length;
        } else {
            invalidFiles += filesData[karyawanId].length;
        }
    }

    const totalBadge = document.getElementById('total_files_badge');
    const validBadge = document.getElementById('valid_files_badge');
    const invalidBadge = document.getElementById('invalid_files_badge');

    if (totalBadge) totalBadge.textContent = totalFiles;
    if (validBadge) validBadge.textContent = validFiles;
    if (invalidBadge) invalidBadge.textContent = invalidFiles;

    // Show success alert if all valid
    if (totalFiles > 0 && invalidFiles === 0) {
        hideGlobalError();
        showGlobalSuccess();
    } else if (invalidFiles > 0) {
        hideGlobalSuccess();
    }
}

function showKaryawanError(karyawanId, karyawanName, message) {
    console.log('Showing error for karyawan:', karyawanId, message);
    
    validationStatus[karyawanId] = false;
    
    const card = document.getElementById('card_' + karyawanId);
    const errorDiv = document.getElementById('errors_' + karyawanId);
    const errorList = document.getElementById('error_list_' + karyawanId);

    if (card) {
        card.classList.add('has-error');
        card.classList.remove('has-success');
    }
    
    if (errorDiv) {
        errorDiv.classList.remove('d-none');
    }
    
    if (errorList) {
        const li = document.createElement('li');
        li.textContent = message;
        errorList.appendChild(li);
    }

    updateGlobalStats();
    showGlobalError(karyawanName + ': ' + message);
}

function clearKaryawanError(karyawanId) {
    console.log('Clearing error for karyawan:', karyawanId);
    
    validationStatus[karyawanId] = true;
    
    const card = document.getElementById('card_' + karyawanId);
    const errorDiv = document.getElementById('errors_' + karyawanId);
    const errorList = document.getElementById('error_list_' + karyawanId);

    if (card) {
        card.classList.remove('has-error');
        if (filesData[karyawanId].length > 0) {
            card.classList.add('has-success');
        } else {
            card.classList.remove('has-success');
        }
    }
    
    if (errorDiv) {
        errorDiv.classList.add('d-none');
    }
    
    if (errorList) {
        errorList.innerHTML = '';
    }

    updateGlobalStats();
    
    // Hide global error if no more errors
    const hasAnyError = Object.values(validationStatus).some(status => status === false);
    if (!hasAnyError) {
        hideGlobalError();
    }
}

function showGlobalError(message) {
    const alert = document.getElementById('global_error_alert');
    const content = document.getElementById('global_error_content');
    
    if (alert && content) {
        content.innerHTML = message;
        alert.classList.remove('d-none');
        alert.classList.add('error-badge');
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function hideGlobalError() {
    const alert = document.getElementById('global_error_alert');
    if (alert) {
        alert.classList.add('d-none');
    }
}

function showGlobalSuccess() {
    const alert = document.getElementById('global_success_alert');
    if (alert) {
        alert.classList.remove('d-none');
    }
}

function hideGlobalSuccess() {
    const alert = document.getElementById('global_success_alert');
    if (alert) {
        alert.classList.add('d-none');
    }
}
</script>
@endpush