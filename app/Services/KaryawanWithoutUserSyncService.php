<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Karyawan;
use App\Models\SyncKaryawanWithoutUser;

class KaryawanWithoutUserSyncService
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

            Log::error('KaryawanWithoutUserSyncService: HTTP error', [
                'endpoint' => $endpoint,
                'status'   => $response->status(),
            ]);

            return ['success' => false, 'message' => 'API Error: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('KaryawanWithoutUserSyncService: Exception', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync dari API absen ke:
     * 1. Tabel sync_karyawan_without_users (staging/cache)
     *    - insert / update / restore / softdelete
     * 2. Tabel karyawans apps gaji
     *    - hanya INSERT yang belum ada (skip yang sudah ada)
     *    - tidak pernah hapus / update data karyawans yang sudah ada
     */
    public function sync(): array
    {
        $syncedAt   = Carbon::now();
        $allFromApi = [];
        $page       = 1;
        $perPage    = 100;

        // ── 1. Ambil semua data dari API (semua page) ─────────────
        do {
            $result = $this->get('karyawan-without-user', [
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
                $allFromApi[$item['id']] = $item; // key by absen_karyawan_id
            }

            $hasMore = ($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1);
            $page++;

        } while ($hasMore);

        if (empty($allFromApi)) {
            return [
                'success' => true,
                'message' => 'Semua karyawan sudah memiliki akun user di apps absen',
                'stats'   => ['sync_inserted' => 0, 'sync_updated' => 0, 'sync_restored' => 0, 'sync_soft_deleted' => 0, 'karyawan_inserted' => 0, 'karyawan_skipped' => 0],
                'total'   => 0,
            ];
        }

        $apiIds = array_keys($allFromApi);
        $stats  = [
            'sync_inserted'    => 0,
            'sync_updated'     => 0,
            'sync_restored'    => 0,
            'sync_soft_deleted'=> 0,
            'karyawan_inserted'=> 0,
            'karyawan_skipped' => 0,
        ];

        DB::beginTransaction();
        try {
            // ── 2. Upsert ke tabel sync (sama polanya dengan KaryawanJabatanPerusahaanServices) ──
            $existingSync = SyncKaryawanWithoutUser::withTrashed()
                ->whereIn('absen_karyawan_id', $apiIds)
                ->get()
                ->keyBy('absen_karyawan_id');

            foreach ($allFromApi as $absenId => $item) {
                $payload = $this->mapApiToPayload($item, $syncedAt);

                if (isset($existingSync[$absenId])) {
                    $record = $existingSync[$absenId];
                    if ($record->trashed()) {
                        $record->restore();
                        $record->update($payload);
                        $stats['sync_restored']++;
                    } else {
                        $record->update($payload);
                        $stats['sync_updated']++;
                    }
                } else {
                    SyncKaryawanWithoutUser::create(
                        array_merge(['absen_karyawan_id' => $absenId], $payload)
                    );
                    $stats['sync_inserted']++;
                }
            }

            // Softdelete yang tidak ada di API
            // (artinya karyawan sudah dapat user di apps absen)
            $toSoftDelete = SyncKaryawanWithoutUser::whereNotIn('absen_karyawan_id', $apiIds)
                ->whereNull('deleted_at')
                ->pluck('absen_karyawan_id');

            if ($toSoftDelete->isNotEmpty()) {
                SyncKaryawanWithoutUser::whereIn('absen_karyawan_id', $toSoftDelete)->delete();
                $stats['sync_soft_deleted'] = $toSoftDelete->count();
            }

            // ── 3. Insert ke tabel karyawans apps gaji (hanya yang belum ada) ──
            $existingKaryawanIds = Karyawan::whereIn('absen_karyawan_id', $apiIds)
                ->pluck('absen_karyawan_id')
                ->flip() // jadi associative untuk O(1) lookup
                ->toArray();

            foreach ($allFromApi as $absenId => $item) {
                if (isset($existingKaryawanIds[$absenId])) {
                    $stats['karyawan_skipped']++;
                    continue;
                }

                Karyawan::create([
                    'absen_karyawan_id'    => $absenId,
                    'nik'                  => $item['nik']                  ?? 'NIK-' . $absenId,
                    'nama_lengkap'         => $item['nama_lengkap'],
                    'email_pribadi'        => $item['email_pribadi']        ?? null,
                    'telp_pribadi'         => $item['telp_pribadi']         ?? null,
                    'join_date'            => $item['join_date']            ?? null,
                    'jenis_kelamin'        => $item['jenis_kelamin']        ?? null,
                    'status_pernikahan'    => $item['status_pernikahan']    ?? 'belum menikah',
                    'tempat_tanggal_lahir' => $item['tempat_tanggal_lahir'] ?? null,
                    'alamat'               => $item['alamat']               ?? null,
                    'no_ktp'               => $item['no_ktp']               ?? '0000000000000000',
                    'no_npwp'              => '-',
                    'status_resign'        => false,
                ]);

                $stats['karyawan_inserted']++;
            }

            DB::commit();

            Log::info('KaryawanWithoutUserSyncService: sync selesai', $stats);

            return [
                'success' => true,
                'message' => "Sync selesai: {$stats['karyawan_inserted']} karyawan baru ditambahkan, {$stats['karyawan_skipped']} dilewati",
                'stats'   => $stats,
                'total'   => count($allFromApi),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('KaryawanWithoutUserSyncService: sync gagal', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Sync gagal: ' . $e->getMessage(),
            ];
        }
    }

    private function mapApiToPayload(array $item, Carbon $syncedAt): array
    {
        return [
            'nik'                  => $item['nik']                  ?? null,
            'nama_lengkap'         => $item['nama_lengkap'],
            'email_pribadi'        => $item['email_pribadi']        ?? null,
            'telp_pribadi'         => $item['telp_pribadi']         ?? null,
            'join_date'            => $item['join_date']            ?? null,
            'jenis_kelamin'        => $item['jenis_kelamin']        ?? null,
            'status_pernikahan'    => $item['status_pernikahan']    ?? null,
            'tempat_tanggal_lahir' => $item['tempat_tanggal_lahir'] ?? null,
            'alamat'               => $item['alamat']               ?? null,
            'no_ktp'               => $item['no_ktp']               ?? null,
            'status_resign'        => $item['status_resign']        ?? false,
            'last_synced_at'       => $syncedAt,
        ];
    }
}

    