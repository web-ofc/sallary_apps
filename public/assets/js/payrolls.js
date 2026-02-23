/**
 * payrolls.js
 * Taruh di: public/assets/js/payrolls.js
 *
 * Dependency: jQuery, DataTables, toastr, SweetAlert2
 * Config injected dari blade via window.PAYROLL_CONFIG
 */
$(document).ready(function () {
    "use strict";

    const CONFIG = window.PAYROLL_CONFIG || {};

    // ========================================
    // STATE
    // ========================================
    let selectedIds = [];
    let selectedReleasedIds = [];
    let selectedReleasedSlipIds = [];

    let deletePayrollId = null;
    let tablePending, tableReleased, tableReleasedSlip;

    // ========================================
    // UTILITY
    // ========================================
    function formatRupiah(value) {
        if (!value || value === 0 || value === "0") return "-";
        const num = typeof value === "string" ? parseFloat(value) : value;
        return (
            "Rp " +
            num.toLocaleString("id-ID", {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            })
        );
    }

    function showToast(message, type = "success") {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000,
        };
        toastr[type](message);
    }

    function updateStatistics(periode = null) {
        const params = periode ? "?periode=" + periode : "";
        $.ajax({
            url: CONFIG.routes.statistics + params,
            method: "POST",
            headers: { "X-CSRF-TOKEN": CONFIG.csrf },
            success: function (res) {
                if (!res.success) return;
                $("#statPendingCount").text(res.data.pending.count);
                $("#statReleasedCount").text(res.data.released.count);
                $("#statReleasedSlipCount").text(res.data.released_slip.count);
                $("#tabPendingCount").text(res.data.pending.count);
                $("#tabReleasedCount").text(res.data.released.count);
                $("#tabReleasedSlipCount").text(res.data.released_slip.count);
            },
        });
    }

    // ========================================
    // COLUMN DEFINITIONS
    // Semua angka dikirim raw dari server,
    // formatting cukup di render callback di sini.
    // ========================================
    const R = (data) => formatRupiah(data); // shorthand

    const COLUMNS_BASE = [
        // 0: checkbox (pending & released punya, released_slip tidak)
        {
            data: "checkbox",
            name: "checkbox",
            orderable: false,
            searchable: false,
            className: "text-center",
        },
        // 1: index
        {
            data: "DT_RowIndex",
            name: "DT_RowIndex",
            orderable: false,
            searchable: false,
            className: "text-center",
        },
        // 2: periode
        { data: "periode", name: "periode", className: "text-center" },
        // 3: nik
        {
            data: "karyawan_nik",
            name: "karyawan.nik",
            className: "text-center",
        },
        // 4: nama
        { data: "karyawan_nama", name: "karyawan.nama_lengkap" },
        // 5: company
        { data: "company_nama", name: "company.company_name" },
        // 6: salary type
        { data: "salary_type", name: "salary_type", className: "text-center" },
        { data: "ptkp_status", name: "ptkp_status", className: "text-center" },
        // 7: gaji pokok
        {
            data: "gaji_pokok",
            name: "gaji_pokok",
            className: "text-end",
            render: R,
        },
        // --- Monthly Insentif (6) ---
        {
            data: "monthly_kpi",
            name: "monthly_kpi",
            className: "text-end",
            render: R,
        },
        {
            data: "overtime",
            name: "overtime",
            className: "text-end",
            render: R,
        },
        {
            data: "medical_reimbursement",
            name: "medical_reimbursement",
            className: "text-end",
            render: R,
        },
        {
            data: "insentif_sholat",
            name: "insentif_sholat",
            className: "text-end",
            render: R,
        },
        {
            data: "monthly_bonus",
            name: "monthly_bonus",
            className: "text-end",
            render: R,
        },
        { data: "rapel", name: "rapel", className: "text-end", render: R },
        // --- Monthly Allowance (4) ---
        {
            data: "tunjangan_pulsa",
            name: "tunjangan_pulsa",
            className: "text-end",
            render: R,
        },
        {
            data: "tunjangan_kehadiran",
            name: "tunjangan_kehadiran",
            className: "text-end",
            render: R,
        },
        {
            data: "tunjangan_transport",
            name: "tunjangan_transport",
            className: "text-end",
            render: R,
        },
        {
            data: "tunjangan_lainnya",
            name: "tunjangan_lainnya",
            className: "text-end",
            render: R,
        },
        // --- Yearly Benefit (3) ---
        {
            data: "yearly_bonus",
            name: "yearly_bonus",
            className: "text-end",
            render: R,
        },
        { data: "thr", name: "thr", className: "text-end", render: R },
        { data: "other", name: "other", className: "text-end", render: R },
        // --- Potongan (6) ---
        {
            data: "ca_corporate",
            name: "ca_corporate",
            className: "text-end",
            render: R,
        },
        {
            data: "ca_personal",
            name: "ca_personal",
            className: "text-end",
            render: R,
        },
        {
            data: "ca_kehadiran",
            name: "ca_kehadiran",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tenaga_kerja",
            name: "bpjs_tenaga_kerja",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_kesehatan",
            name: "bpjs_kesehatan",
            className: "text-end",
            render: R,
        },
        {
            data: "pph_21_deduction",
            name: "pph_21_deduction",
            className: "text-end",
            render: R,
        },
        // --- BPJS TK (6) ---
        {
            data: "bpjs_tk_jht_3_7_percent",
            name: "bpjs_tk_jht_3_7_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tk_jht_2_percent",
            name: "bpjs_tk_jht_2_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tk_jkk_0_24_percent",
            name: "bpjs_tk_jkk_0_24_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tk_jkm_0_3_percent",
            name: "bpjs_tk_jkm_0_3_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tk_jp_2_percent",
            name: "bpjs_tk_jp_2_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_tk_jp_1_percent",
            name: "bpjs_tk_jp_1_percent",
            className: "text-end",
            render: R,
        },
        // --- BPJS KES (2) ---
        {
            data: "bpjs_kes_4_percent",
            name: "bpjs_kes_4_percent",
            className: "text-end",
            render: R,
        },
        {
            data: "bpjs_kes_1_percent",
            name: "bpjs_kes_1_percent",
            className: "text-end",
            render: R,
        },
        // --- Lainnya (5) ---
        { data: "pph_21", name: "pph_21", className: "text-end", render: R },
        { data: "glh", name: "glh", className: "text-end", render: R },
        { data: "lm", name: "lm", className: "text-end", render: R },
        { data: "lainnya", name: "lainnya", className: "text-end", render: R },
        {
            data: "tunjangan",
            name: "tunjangan",
            className: "text-end",
            render: R,
        },
        // --- Summary (4) ---
        {
            data: "salary",
            name: "salary",
            className: "text-end fw-bold",
            render: R,
        },
        {
            data: "total_penerimaan",
            name: "total_penerimaan",
            className: "text-end fw-bold",
            render: R,
        },
        {
            data: "total_potongan",
            name: "total_potongan",
            className: "text-end fw-bold",
            render: (data) => formatRupiah(Math.abs(data || 0)),
        },
        {
            data: "gaji_bersih",
            name: "gaji_bersih",
            className: "text-end fw-bold",
            render: R,
        },
        // --- Action ---
        {
            data: "action",
            name: "action",
            orderable: false,
            searchable: false,
            className: "text-center",
        },
    ];

    // Released Slip: tanpa kolom checkbox (index 0)
    const COLUMNS_NO_CHECKBOX = COLUMNS_BASE.filter(
        (c) => c.data !== "checkbox",
    );

    // ========================================
    // DATATABLE SHARED OPTIONS
    // ========================================
    function dtOptions(url, filterId, columns, orderCol = 2) {
        return {
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: url,
                type: "POST",
                headers: { "X-CSRF-TOKEN": CONFIG.csrf },
                data: function (d) {
                    d.periode = $("#" + filterId).val();
                },
            },
            columns: columns,
            order: [[orderCol, "desc"]],
            scrollCollapse: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"],
            ],
            scrollX: true,
            fixedColumns: { leftColumns: 5 },
        };
    }

    // ========================================
    // INIT DATATABLES
    // ========================================
    if ($("#payrollTablePending").length) {
        tablePending = $("#payrollTablePending").DataTable(
            dtOptions(
                CONFIG.routes.pending,
                "filterPeriodePending",
                COLUMNS_BASE,
                2,
            ),
        );
    }

    if ($("#payrollTableReleased").length) {
        tableReleased = $("#payrollTableReleased").DataTable(
            dtOptions(
                CONFIG.routes.released,
                "filterPeriodeReleased",
                COLUMNS_BASE,
                2,
            ),
        );
    }

    if ($("#payrollTableReleasedSlip").length) {
        tableReleasedSlip = $("#payrollTableReleasedSlip").DataTable(
            dtOptions(
                CONFIG.routes.releasedSlip,
                "filterPeriodeReleasedSlip",
                COLUMNS_BASE,
                2,
            ),
        );
    }

    // ========================================
    // SEARCH + FILTER EVENTS
    // ========================================
    function bindSearchAndFilter(searchId, filterId, table) {
        $("#" + searchId).on("keyup", function () {
            if (table) table.search(this.value).draw();
        });

        $("#" + filterPeriodeId(filterId)).on("change", function () {
            if (table) table.ajax.reload();
        });
    }

    // Helper: filterPeriode id sudah ada di HTML, cukup bind change
    function filterPeriodeId(id) {
        return id;
    }

    // Pending
    $("#searchPending").on("keyup", function () {
        if (tablePending) tablePending.search(this.value).draw();
    });
    $("#filterPeriodePending").on("change", function () {
        if (tablePending) tablePending.ajax.reload();
    });
    $("#btnResetFilterPending").on("click", function () {
        $("#filterPeriodePending").val("");
        $("#searchPending").val("");
        if (tablePending) tablePending.search("").ajax.reload();
    });

    // Released
    $("#searchReleased").on("keyup", function () {
        if (tableReleased) tableReleased.search(this.value).draw();
    });
    $("#filterPeriodeReleased").on("change", function () {
        if (tableReleased) tableReleased.ajax.reload();
    });
    $("#btnResetFilterReleased").on("click", function () {
        $("#filterPeriodeReleased").val("");
        $("#searchReleased").val("");
        if (tableReleased) tableReleased.search("").ajax.reload();
    });

    // Released Slip
    $("#searchReleasedSlip").on("keyup", function () {
        if (tableReleasedSlip) tableReleasedSlip.search(this.value).draw();
    });
    $("#filterPeriodeReleasedSlip").on("change", function () {
        if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
    });
    $("#btnResetFilterReleasedSlip").on("click", function () {
        $("#filterPeriodeReleasedSlip").val("");
        $("#searchReleasedSlip").val("");
        if (tableReleasedSlip) tableReleasedSlip.search("").ajax.reload();
    });

    // ========================================
    // STATISTIK FILTER
    // ========================================
    $("#filterStatisticsPeriode").on("change", function () {
        const v = $(this).val();
        if (v) updateStatistics(v);
    });
    $("#filterStatisticsYear").on("change", function () {
        const v = $(this).val();
        if (v) updateStatistics(v);
    });
    $("#btnResetStatistics").on("click", function () {
        $("#filterStatisticsPeriode").val("");
        $("#filterStatisticsYear").val("");
        updateStatistics();
    });

    // ========================================
    // CHECKBOX — PENDING
    // ========================================
    $("#checkAllPending").on("change", function () {
        $(".row-checkbox").prop("checked", $(this).is(":checked"));
        updateSelectedIds();
    });
    $(document).on("change", ".row-checkbox", function () {
        updateSelectedIds();
    });

    function updateSelectedIds() {
        selectedIds = $(".row-checkbox:checked")
            .map(function () {
                return $(this).val();
            })
            .get();
        $("#selectedCount").text(selectedIds.length);
        $("#btnReleaseSelected").toggle(selectedIds.length > 0);
        $("#checkAllPending").prop(
            "checked",
            $(".row-checkbox").length > 0 &&
                $(".row-checkbox").length === $(".row-checkbox:checked").length,
        );
    }

    // ========================================
    // CHECKBOX — RELEASED
    // ========================================
    $("#checkAllReleased").on("change", function () {
        $(".row-checkbox-released").prop("checked", $(this).is(":checked"));
        updateSelectedReleasedIds();
    });
    $(document).on("change", ".row-checkbox-released", function () {
        updateSelectedReleasedIds();
    });

    function updateSelectedReleasedIds() {
        selectedReleasedIds = $(".row-checkbox-released:checked")
            .map(function () {
                return $(this).val();
            })
            .get();

        $("#selectedReleasedCount").text(selectedReleasedIds.length);
        $("#selectedReleasedCountDownload").text(selectedReleasedIds.length);

        $("#btnReleaseSlipSelected").toggle(selectedReleasedIds.length > 0);
        $("#btnDownloadPdfSelected").toggle(selectedReleasedIds.length > 0);

        $("#checkAllReleased").prop(
            "checked",
            $(".row-checkbox-released").length > 0 &&
                $(".row-checkbox-released").length ===
                    $(".row-checkbox-released:checked").length,
        );
    }

    $("#btnDownloadPdfSelected").on("click", function (e) {
        e.preventDefault();

        if (!selectedReleasedIds.length) return;

        if (selectedReleasedIds.length > 20) {
            showToast(
                "Maksimal download 20 slip dalam sekali proses.",
                "error",
            );
            return;
        }

        // POST form biar langsung download file (ZIP)
        const form = document.createElement("form");
        form.method = "POST";
        form.action = CONFIG.routes.downloadZip; // kita set di blade config

        const csrf = document.createElement("input");
        csrf.type = "hidden";
        csrf.name = "_token";
        csrf.value = CONFIG.csrf;
        form.appendChild(csrf);

        selectedReleasedIds.forEach((id) => {
            const inp = document.createElement("input");
            inp.type = "hidden";
            inp.name = "ids[]";
            inp.value = id;
            form.appendChild(inp);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });



    // ========================================
    // RELEASE (Pending → Released)
    // ========================================
    $("#btnReleaseSelected").on("click", function () {
        $("#releaseCountText").text(selectedIds.length);
        $("#releaseSlipCheck").prop("checked", false);
        $("#modalReleaseConfirm").modal("show");
    });

    $("#btnConfirmRelease").on("click", function () {
        const btn = $(this);
        const doSlip = $("#releaseSlipCheck").is(":checked");

        btn.prop("disabled", true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...',
        );

        $.ajax({
            url: CONFIG.routes.release,
            method: "POST",
            headers: { "X-CSRF-TOKEN": CONFIG.csrf },
            data: { ids: selectedIds, release_slip: doSlip ? "1" : "0" },
            success: function (res) {
                $("#modalReleaseConfirm").modal("hide");
                showToast(res.message, "success");
                if (tablePending) tablePending.ajax.reload();
                if (tableReleased) tableReleased.ajax.reload();
                if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
                updateStatistics();
                selectedIds = [];
                $("#selectedCount").text(0);
                $("#btnReleaseSelected").hide();
                $("#checkAllPending").prop("checked", false);
            },
            error: function (xhr) {
                showToast(
                    xhr.responseJSON?.message || "Gagal merilis payroll",
                    "error",
                );
            },
            complete: function () {
                btn.prop("disabled", false).html(
                    '<i class="ki-outline ki-check-circle fs-2"></i> Ya, Release Sekarang',
                );
            },
        });
    });

    // ========================================
    // RELEASE SLIP (Released → Released Slip)
    // ========================================
    $("#btnReleaseSlipSelected").on("click", function () {
        $("#releaseSlipCountText").text(selectedReleasedIds.length);
        $("#modalReleaseSlipConfirm").modal("show");
    });

    $("#btnConfirmReleaseSlip").on("click", function () {
        const btn = $(this);
        btn.prop("disabled", true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...',
        );

        $.ajax({
            url: CONFIG.routes.release,
            method: "POST",
            headers: { "X-CSRF-TOKEN": CONFIG.csrf },
            data: { ids: selectedReleasedIds, release_slip: "1" },
            success: function (res) {
                $("#modalReleaseSlipConfirm").modal("hide");
                showToast(res.message, "success");
                if (tableReleased) tableReleased.ajax.reload();
                if (tableReleasedSlip) tableReleasedSlip.ajax.reload();
                updateStatistics();
                selectedReleasedIds = [];
                $("#selectedReleasedCount").text(0);
                $("#btnReleaseSlipSelected").hide();
                $("#checkAllReleased").prop("checked", false);
            },
            error: function (xhr) {
                showToast(
                    xhr.responseJSON?.message || "Gagal merilis slip gaji",
                    "error",
                );
            },
            complete: function () {
                btn.prop("disabled", false).html(
                    '<i class="ki-outline ki-double-check fs-2"></i> Ya, Release Slip Sekarang',
                );
            },
        });
    });

    // ========================================
    // CHECKBOX — RELEASED SLIP
    // ========================================
    $(document).on("change", ".row-checkbox-released-slip", function () {
        updateSelectedReleasedSlipIds();
    });

    $("#checkAllReleasedSlip").on("change", function () {
        $(".row-checkbox-released-slip").prop("checked", $(this).is(":checked"));
        updateSelectedReleasedSlipIds();
    });

    function updateSelectedReleasedSlipIds() {
        selectedReleasedSlipIds = $(".row-checkbox-released-slip:checked")
            .map(function () { return $(this).val(); })
            .get();

        $("#selectedReleasedSlipCount").text(selectedReleasedSlipIds.length);

        // tampilkan tombol kalau ada selection
        $("#btnDownloadZipReleasedSlip").toggle(selectedReleasedSlipIds.length > 0);

        // limit 20 (UX)
        if (selectedReleasedSlipIds.length > 20) {
            showToast("Maksimal pilih 20 payroll untuk download ZIP", "error");
            // uncheck yang terakhir dicentang (paling gampang: matiin yang baru)
            $(document.activeElement).prop("checked", false);
            updateSelectedReleasedSlipIds();
            return;
        }

        $("#checkAllReleasedSlip").prop(
            "checked",
            $(".row-checkbox-released-slip").length > 0 &&
            $(".row-checkbox-released-slip").length === $(".row-checkbox-released-slip:checked").length
        );
    }

    $("#btnDownloadZipReleasedSlip").on("click", function (e) {
        e.preventDefault();

        if (selectedReleasedSlipIds.length < 1) {
            showToast("Pilih minimal 1 data", "error");
            return;
        }
        if (selectedReleasedSlipIds.length > 20) {
            showToast("Maksimal 20 data", "error");
            return;
        }

        // POST form biar browser langsung download file
        const form = document.createElement("form");
        form.method = "POST";
        form.action = CONFIG.routes.downloadZip;

        const csrf = document.createElement("input");
        csrf.type = "hidden";
        csrf.name = "_token";
        csrf.value = CONFIG.csrf;
        form.appendChild(csrf);

        selectedReleasedSlipIds.forEach((id) => {
            const inp = document.createElement("input");
            inp.type = "hidden";
            inp.name = "ids[]";
            inp.value = id;
            form.appendChild(inp);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });


    // ========================================
    // DELETE
    // ========================================
    $(document).on("click", ".btn-delete", function () {
        deletePayrollId = $(this).data("id");
        $("#deletePeriodeText").text($(this).data("periode"));
        $("#modalDeleteConfirm").modal("show");
    });

    $("#btnConfirmDelete").on("click", function () {
        if (!deletePayrollId) return;
        const btn = $(this);
        btn.prop("disabled", true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Menghapus...',
        );

        $.ajax({
            url: CONFIG.routes.destroy.replace(":id", deletePayrollId),
            method: "DELETE",
            headers: { "X-CSRF-TOKEN": CONFIG.csrf },
            success: function (res) {
                $("#modalDeleteConfirm").modal("hide");
                showToast(res.message, "success");
                if (tablePending) tablePending.ajax.reload();
                updateStatistics();
            },
            error: function (xhr) {
                showToast(
                    xhr.responseJSON?.message || "Gagal menghapus payroll",
                    "error",
                );
            },
            complete: function () {
                btn.prop("disabled", false).html(
                    '<i class="ki-outline ki-trash fs-2"></i> Ya, Hapus Sekarang',
                );
                deletePayrollId = null;
            },
        });
    });

    // ========================================
    // EXPORT (fetch + blob download)
    // ========================================
    function downloadExport(status, periode) {
        const form = document.createElement("form");
        form.method = "POST";
        form.action = CONFIG.routes.export;

        const csrf = document.createElement("input");
        csrf.type = "hidden";
        csrf.name = "_token";
        csrf.value = CONFIG.csrf;
        form.appendChild(csrf);

        const s = document.createElement("input");
        s.type = "hidden";
        s.name = "status";
        s.value = status;
        form.appendChild(s);

        if (periode) {
            const p = document.createElement("input");
            p.type = "hidden";
            p.name = "periode";
            p.value = periode;
            form.appendChild(p);
        }

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }


    $("#btnExportPending").on("click", function (e) {
        e.preventDefault();
        downloadExport("pending", $("#filterPeriodePending").val());
    });
    $("#btnExportReleased").on("click", function (e) {
        e.preventDefault();
        downloadExport("released", $("#filterPeriodeReleased").val());
    });
    $("#btnExportReleasedSlip").on("click", function (e) {
        e.preventDefault();
        downloadExport("released_slip", $("#filterPeriodeReleasedSlip").val());
    });

    // ========================================
    // TAB SWITCH → adjust columns
    // ========================================
    $('a[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
        const target = $(e.target).attr("href");
        if (target === "#tab_pending" && tablePending)
            tablePending.columns.adjust().draw();
        if (target === "#tab_released" && tableReleased)
            tableReleased.columns.adjust().draw();
        if (target === "#tab_released_slip" && tableReleasedSlip)
            tableReleasedSlip.columns.adjust().draw();
    });

    // Responsive resize
    $(window).on("resize", function () {
        if (tablePending) tablePending.columns.adjust();
        if (tableReleased) tableReleased.columns.adjust();
        if (tableReleasedSlip) tableReleasedSlip.columns.adjust();
    });
});
