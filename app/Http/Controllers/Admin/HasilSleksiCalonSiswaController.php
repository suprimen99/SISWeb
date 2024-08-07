<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use App\Helpers\GeneralHelpers;
use App\Helpers\PaymentHelpers;
use App\Mail\HasilSeleksiEmail;
use App\Helpers\ResponseHelpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Models\HasilSleksiCalonSiswa;
use App\Models\HasilSeleksiCalonSiswa;
use Illuminate\Support\Facades\Validator;

class HasilSleksiCalonSiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search_data = $request->query('search_data');

        $query = Pendaftaran::with('CalonWaliPendaftaran.Users', 'CalonSiswaPendaftaran.JenisKelaminCalonSiswa', 'InfoBiayaPendaftaran.BiayaPendaftaran')
            ->where('row_status', 0)
            ->where('status_seleksi', 'belum_dinilai')
            ->whereHas('DokumenCalonSiswa', function ($q) {
                $q->where('status', 'valid')
                    ->havingRaw('COUNT(*) = 3');
            })
            ->orderBy('id', 'desc');

        if (!empty($search_data)) {
            $query->where('kode_pendaftaran', 'like', '%' . $search_data . '%');
        }

        $data = $query->paginate(10)->onEachSide(2)->fragment('transaksi');

        return view('AdminView.InfoHasilSleksi.index', compact('data', 'search_data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'membaca' => 'required|in:1,2,3,4,5,6,7,8,9,10',
                'menulis' => 'required|in:1,2,3,4,5,6,7,8,9,10'
            ]
        );

        if ($validator->fails()) {
            return ResponseHelpers::ErrorResponse($validator->messages(), 400);
        }

        DB::beginTransaction();
        try {

            // Bobot masing-masing tes 40%
            $bobot_membaca = 0.5;
            $bobot_menulis = 0.5;

            // Hitung nilai akumulatif dengan mempertimbangkan bobot
            $nilai_akumulatif = ($request->membaca * $bobot_membaca) + ($request->menulis * $bobot_menulis);

            // Batas nilai minimum untuk lulus
            $batas_lulus = 6.8;

            // Nilai akumulatif ke dalam variabel hasil
            $total = sprintf("%.1f", $nilai_akumulatif);

            $hasil = "";
            // Menentukan lulus
            if ($total >= $batas_lulus) {
                $hasil = "lolos";
            } else {
                $hasil = "tidak_lolos";
            }

            $data_pendaftaran = Pendaftaran::with('CalonWaliPendaftaran.Users', 'CalonSiswaPendaftaran')
                ->where('kode_pendaftaran', $request->kode_pendaftaran)
                ->where('row_status', 0)
                ->first();


            if ($data_pendaftaran != null) {
                $data = new HasilSeleksiCalonSiswa();

                $data->pendaftaran_id = $data_pendaftaran->id;
                $data->membaca = $request->membaca;
                $data->menulis = $request->menulis;
                $data->hasil = $total;
                $data->status = $hasil;


                GeneralHelpers::setCreatedAt($data);
                GeneralHelpers::setCreatedBy($data);
                GeneralHelpers::setUpdatedAtNull($data);
                GeneralHelpers::setRowStatusActive($data);

                $data->save();

                if ($hasil == "lolos" || $hasil == "tidak_lolos") {
                    //dd($hasil);
                    $data_pendaftaran->status_seleksi = $hasil;
                    GeneralHelpers::setUpdatedAtNull($data_pendaftaran);

                    if ($hasil == "tidak_lolos") {
                        $data_pendaftaran->is_bayar = PaymentHelpers::setFalse();
                        $data_pendaftaran->token_pembayaran = "-";
                        PaymentHelpers::setFailed($data_pendaftaran);
                    }

                    $data_pendaftaran->save();

                    $email_user = $data_pendaftaran->CalonWaliPendaftaran->Users->email;
                    $calon_siswa = $data_pendaftaran->CalonSiswaPendaftaran->nama_lengkap;
                    $wali_siswa = $data_pendaftaran->CalonWaliPendaftaran->Users->username;

                    $mail_data = [
                        'to' => $email_user,
                        'nilai_membaca' => $data->membaca,
                        'nilai_menulis' => $data->menulis,
                        'nilai_akumulatif' => $data->hasil,
                        'status' => $data->status,
                        'calon_siswa' => $calon_siswa,
                        'wali_siswa' => $wali_siswa,
                    ];

                    Mail::to($email_user)->send(new HasilSeleksiEmail($mail_data));
                }

                DB::commit();
                return ResponseHelpers::SuccessResponse('Data berhasil ditambahkan', '', 200);
            } else {
                return ResponseHelpers::ErrorResponse('Internal server error, try again later', 500);
            }
        } catch (Exception $th) {
            DB::rollBack();
            return ResponseHelpers::ErrorResponse('Internal server error, try again later' . $th, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $seleksi = HasilSleksiCalonSiswa::where('id', $id)->where('row_status', '0')->firstOrFail();
            return ResponseHelpers::SuccessResponse('', $seleksi, 200);
        } catch (Exception $th) {
            return ResponseHelpers::ErrorResponse('Internal server error, try again later', 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'hasil' => 'nullable|max:500'

            ]
        );

        if ($validator->fails()) {
            return ResponseHelpers::ErrorResponse($validator->messages(), 400);
        }

        try {
            if ($id == $request->id) {
                $seleksi = HasilSleksiCalonSiswa::where('id', $id)
                    ->where('row_status', '0')
                    ->firstOrFail();
                $seleksi->hasil = $request->hasil;
                GeneralHelpers::setUpdatedAt($seleksi);

                $seleksi->save();

                return ResponseHelpers::SuccessResponse('Your record has been updated', '', 200);
            } else {
                return ResponseHelpers::ErrorResponse('Internal server error, try again later', 500);
            }
        } catch (Exception $th) {
            return ResponseHelpers::ErrorResponse('Internal server error, try again later', 500);
        }
    }

    public function updateStatusTest($id, $status)
    {
        try {
            $seleksi = HasilSleksiCalonSiswa::where('id', $id)
                ->where('role', 'siswa')
                ->firstOrFail();
            if (($status == '0' || $status == '-1') && $seleksi->row_status != $status) {
                $seleksi->row_status = $status;
                $seleksi->save();

                return ResponseHelpers::SuccessResponse('Your record has been updated', '', 200);
            } else {
                return ResponseHelpers::ErrorResponse('The status has been updated, it cannot be updated again!', 500);
            }
        } catch (Exception $ex) {
            return ResponseHelpers::ErrorResponse('Internal server error, try again later', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
