<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Helpers\GeneralHelpers;
use App\Helpers\ResponseHelpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HasilSleksiCalonSiswa;

class HasilSleksiCalonSiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('AdminView.InfoHasilSleksi.index');
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
        //
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
