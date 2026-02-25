<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SyncKaryawanJabatanPerusahaan;

class KaryawanJabatanPerusahaanServices
{
    protected string $baseUrl;
    protected string $token;
    protected int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.attendance.url', ''), '/');
        $this->token   = config('services.attendance.token', '');
        $this->timeout = 15;
    }

    private function get(string $endpoint, array $params = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->token)
                ->withHeaders(['Accept' => 'application/json'])
                ->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('KaryawanJabatanPerusahaanServices: HTTP error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
            ]);

            return ['success' => false, 'message' => 'API Error: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('KaryawanJabatanPerusahaanServices: Exception', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * FORCE SYNC — dipanggil waktu user klik "Tambah Reimbursement"
     *
     * Rules softdelete:
     *   - Ada di API, tidak ada di DB          → INSERT baru
     *   - Ada di API, ada di DB aktif          → UPDATE
     *   - Ada di API, ada di DB tapi trashed   → RESTORE + UPDATE
     *   - Tidak ada di API, ada di DB aktif    → SOFT DELETE
     */
    public function forceSync(): array
    {
        $syncedAt   = Carbon::now();
        $allFromApi = [];
        $page       = 1;
        $perPage    = 100;

        // 1. Ambil semua data dari API (semua page)
        do {
            $result = $this->get('karyawan-jabatan-perusahaan', [
                'page'     => $page,
                'per_page' => $perPage,
            ]);

            if (!($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengambil data dari API: ' . ($result['message'] ?? 'Unknown error'),
                ];
            }

            $data       = $result['data'] ?? [];
            $pagination = $result['pagination'] ?? [];

            foreach ($data as $item) {
                // key by absen_karyawan_id
                $allFromApi[$item['id']] = $item;
            }

            $hasMore = ($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1);
            $page++;

        } while ($hasMore);

        if (empty($allFromApi)) {
            return ['success' => true, 'message' => 'Tidak ada data dari API', 'synced' => 0];
        }

        $apiIds = array_keys($allFromApi);
        $stats  = ['inserted' => 0, 'updated' => 0, 'restored' => 0, 'soft_deleted' => 0];

        DB::beginTransaction();
        try {
            // 2. Ambil semua record DB (termasuk softdeleted) untuk ID yang ada di API
            $existingRecords = SyncKaryawanJabatanPerusahaan::withTrashed()
                ->whereIn('absen_karyawan_id', $apiIds)
                ->get()
                ->keyBy('absen_karyawan_id');

            // 3. Upsert: insert / update / restore
            foreach ($allFromApi as $absenId => $item) {
                $payload = $this->mapApiToPayload($item, $syncedAt);

                if (isset($existingRecords[$absenId])) {
                    $record = $existingRecords[$absenId];
                    if ($record->trashed()) {
                        $record->restore();
                        $record->update($payload);
                        $stats['restored']++;
                    } else {
                        $record->update($payload);
                        $stats['updated']++;
                    }
                } else {
                    SyncKaryawanJabatanPerusahaan::create(
                        array_merge(['absen_karyawan_id' => $absenId], $payload)
                    );
                    $stats['inserted']++;
                }
            }

            // 4. Softdelete yang TIDAK ada di API (sudah tidak aktif di apps absen)
            $toSoftDelete = SyncKaryawanJabatanPerusahaan::whereNotIn('absen_karyawan_id', $apiIds)
                ->whereNull('deleted_at')
                ->pluck('absen_karyawan_id');

            if ($toSoftDelete->isNotEmpty()) {
                SyncKaryawanJabatanPerusahaan::whereIn('absen_karyawan_id', $toSoftDelete)->delete();
                $stats['soft_deleted'] = $toSoftDelete->count();
            }

            DB::commit();

            Log::info('KaryawanJabatanPerusahaanServices: sync selesai', $stats);

            return [
                'success' => true,
                'message' => "Sync selesai: {$stats['inserted']} baru, {$stats['updated']} diperbarui, "
                           . "{$stats['restored']} dipulihkan, {$stats['soft_deleted']} dinonaktifkan",
                'stats'   => $stats,
                'total'   => count($allFromApi),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KaryawanJabatanPerusahaanServices: sync gagal', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Sync gagal: ' . $e->getMessage()];
        }
    }

    /**
     * Search dari tabel lokal (cepat, tidak hit API lagi)
     * Filter: hanya yang punya medical via scope hasMedical
     */
    public function searchLocal(string $search = '', int $page = 1, int $perPage = 15): array
    {
        // ✅ Join ke master_salaries untuk filter status_medical = '1'
        // Ini gantikan whereHas agar lebih efisien (no N+1)
        $query = SyncKaryawanJabatanPerusahaan::query()
            ->join('master_salaries', function ($join) {
                $join->on('master_salaries.karyawan_id', '=', 'sync_karyawan_jabatan_perusahaans.absen_karyawan_id')
                     ->where('master_salaries.status_medical', '1');
            })
            ->select('sync_karyawan_jabatan_perusahaans.*') // hindari kolom ambiguous
            ->distinct()
            ->orderBy('sync_karyawan_jabatan_perusahaans.nama_lengkap');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sync_karyawan_jabatan_perusahaans.nama_lengkap', 'LIKE', "%{$search}%")
                  ->orWhere('sync_karyawan_jabatan_perusahaans.nik', 'LIKE', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage, ['sync_karyawan_jabatan_perusahaans.*'], 'page', $page);

        $results = collect($paginator->items())->map(function ($row) {
            return [
                'id'   => $row->absen_karyawan_id, // ✅ selalu absen_*, bukan id lokal
                'text' => $row->nama_lengkap . ' (NIK: ' . ($row->nik ?: '-') . ')',

                // ✅ company_suggestion langsung dari tabel sync — tidak perlu hit API lagi
                'company_suggestion' => $row->absen_company_id ? [
                    'absen_company_id' => $row->absen_company_id,
                    'company_code'     => $row->company_code,
                    'company_name'     => $row->company_name,
                    'company_logo'     => $row->company_logo,
                    'tgl_mutasi'       => $row->tgl_mutasi_perusahaan?->toDateString(),
                ] : null,

                'jabatan' => $row->absen_jabatan_id ? [
                    'id'           => $row->absen_jabatan_id,
                    'kode_jabatan' => $row->kode_jabatan,
                    'nama_jabatan' => $row->nama_jabatan,
                    'tgl_mutasi'   => $row->tgl_mutasi_jabatan?->toDateString(),
                ] : null,
            ];
        })->values()->all();

        return [
            'results'    => $results,
            'pagination' => ['more' => $paginator->hasMorePages()],
        ];
    }

    private function mapApiToPayload(array $item, Carbon $syncedAt): array
    {
        $jabatan    = $item['jabatan']            ?? [];
        $suggestion = $item['company_suggestion'] ?? [];

        return [
            'nik'                   => $item['nik']       ?? null,
            'nama_lengkap'          => $item['nama_lengkap'],
            'email'                 => $item['email']     ?? null,
            'join_date'             => $item['join_date'] ?? null,

            'absen_jabatan_id'      => $jabatan['id']          ?? null,
            'kode_jabatan'          => $jabatan['kode_jabatan'] ?? null,
            'nama_jabatan'          => $jabatan['nama_jabatan'] ?? null,
            'tgl_mutasi_jabatan'    => $jabatan['tgl_mutasi']   ?? null,

            'absen_company_id'      => $suggestion['absen_company_id'] ?? null,
            'company_code'          => $suggestion['company_code']     ?? null,
            'company_name'          => $suggestion['company_name']     ?? null,
            'company_logo'          => $suggestion['company_logo']     ?? null,
            'tgl_mutasi_perusahaan' => $suggestion['tgl_mutasi']       ?? null,

            'last_synced_at'        => $syncedAt,
        ];
    }
}