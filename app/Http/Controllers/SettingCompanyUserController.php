<?php

namespace App\Http\Controllers;

use App\Models\SettingCompanyUser;
use App\Http\Requests\StoreSettingCompanyUserRequest;
use App\Http\Requests\UpdateSettingCompanyUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class SettingCompanyUserController extends Controller
{
     public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::denies('manage-setting-admin-user')) {
                abort(403, 'Unauthorized action.');
            }

            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.dashboard-admin.setting-admin-user.index', [
            'title' => 'List',
        ]);
    }

    public function getData()
    {
        $settingCompanyUser = SettingCompanyUser::with('user', 'company');

        return DataTables::of($settingCompanyUser)
            ->addColumn('user', function ($list) {
                return $list->user ? $list->user->name : '';
            })
            ->addColumn('company', function ($list) {
                return $list->company ? $list->company->company_name : '';
            })
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('role', 'admin')->get();
        $companies = Company::all();

        return view('dashboard.dashboard-admin.setting-admin-user.add', compact('companies', 'users'))
            ->with('title', 'Add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'absen_company_id' => 'required|array',
            'absen_company_id.*' => 'exists:companies,absen_company_id', // Validasi berdasarkan absen_company_id
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Variabel untuk menyimpan pesan error
        $errors = [];

        // Loop untuk menyimpan setiap kombinasi user_id dan absen_company_id
        foreach ($request->absen_company_id as $companyId) {
            // Cek apakah kombinasi user_id dan absen_company_id sudah ada
            $existingRelation = SettingCompanyUser::where('user_id', $request->user_id)
                ->where('absen_company_id', $companyId)
                ->first();

            // Jika relasi sudah ada, tambahkan pesan error dan hentikan proses
            if ($existingRelation) {
                // Ambil nama company untuk pesan yang lebih informatif
                $company = Company::where('absen_company_id', $companyId)->first();
                $companyName = $company ? $company->company_name : $companyId;
                $errors[] = "The combination of User and Company '{$companyName}' already exists.";
            } else {
                // Menyimpan data baru ke dalam setting_company_users
                SettingCompanyUser::create([
                    'user_id' => $request->user_id,
                    'absen_company_id' => $companyId,
                ]);
            }
        }

        // Jika ada error, kembalikan dengan pesan error
        if (count($errors) > 0) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        // Redirect dengan sukses jika tidak ada error
        return redirect()->route('manage-setting-admin-user.index')
            ->with('success', 'Data saved successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Cari data berdasarkan ID
        $settingCompanyUser = SettingCompanyUser::findOrFail($id);

        // Ambil data pengguna dan perusahaan untuk form
        $users = User::where('role', 'admin')->get();
        $companies = Company::all();

        // Kirim data ke view
        return view('dashboard.dashboard-admin.setting-admin-user.edit', compact('settingCompanyUser', 'users', 'companies'))
            ->with('title', 'Edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'absen_company_id' => 'required|exists:companies,absen_company_id', // Validasi berdasarkan absen_company_id
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Cari data yang ingin diupdate
        $settingCompanyUser = SettingCompanyUser::findOrFail($id);

        // Cek apakah kombinasi user_id dan absen_company_id sudah ada (kecuali data yang sedang diupdate)
        $existingRelation = SettingCompanyUser::where('user_id', $request->user_id)
            ->where('absen_company_id', $request->absen_company_id)
            ->where('id', '!=', $id)
            ->first();

        // Jika relasi sudah ada, kembalikan error dengan notifikasi
        if ($existingRelation) {
            return redirect()->back()
                ->withErrors(['error' => 'The combination of User and Company already exists.'])
                ->withInput();
        }

        // Update data
        $settingCompanyUser->update([
            'user_id' => $request->user_id,
            'absen_company_id' => $request->absen_company_id,
        ]);

        // Redirect dengan pesan sukses
        return redirect()->route('manage-setting-admin-user.index')
            ->with('success', 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
     public function destroy($id)
    {
        try {
            // Cari data berdasarkan ID
            $settingCompanyUser = SettingCompanyUser::findOrFail($id);

            // Hapus data yang ditemukan
            $settingCompanyUser->delete();

            // Return JSON response untuk AJAX
            return response()->json([
                'success' => true,
                'message' => 'Data deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting data: ' . $e->getMessage()
            ], 500);
        }
    }
}