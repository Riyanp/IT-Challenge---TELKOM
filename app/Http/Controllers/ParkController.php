<?php namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Storage;
use JWTAuth;
use Response;
use App\Repository\Transformers\ParkTransformer;
use Validator;


class ParkController extends ApiController
{
    /**
     * @var \App\Repository\Transformers\ParkTransformer
     * */
    protected $parkTransformer;


    public function __construct(ParkTransformer $parkTransformer)
    {

        $this->parkTransformer = $parkTransformer;

    }

    function storeCar(Request $request)
    {
        //Get exsisting car and parking lot value
        $exsistingCar = json_decode(Storage::disk('local')->get('car.json'), true);
        $exsistingPark = json_decode(Storage::disk('local')->get('parking_lot.json'), true);

        //Get available parking lot
        $parking_lot = null;
        foreach ($exsistingPark as $parks => $value) {
            if ($value['status'] == 'kosong') {
                $parking_lot = $value['parking_lot'];
                break;
            }
        }

        //Get request value
        $inputData = $request->only(['plat_nomor', 'tipe', 'warna']);
        date_default_timezone_set("Asia/Jakarta");
        $inputData['tanggal_masuk'] = date('Y-m-d H:i:s');
        $inputData['parking_lot'] = $parking_lot;
        $message = null;

        //Validasi plat nomor jika sama atau jika tempat parkir penuh
        foreach ($exsistingCar as $cars => $value) {
            if ($value['plat_nomor'] == $request['plat_nomor']) {
                $inputData = null;
                $message['response'] = 'Plat nomor sudah terdaftar';
                break;
            } else if ($inputData['parking_lot'] == null) {
                $inputData = null;
                $message['response'] = 'Tempat Parkir sudah ditempati/penuh';
            }
        }

        //Insert data mobil dan update tempat parkir
        array_push($exsistingCar, $inputData);
        if ($inputData != null) {
            Storage::disk('local')->put('car.json', json_encode($exsistingCar));
            foreach ($exsistingPark as $parks => $value) {
                if ($value['parking_lot'] == $parking_lot) {
                    $exsistingPark[$parks]['status'] = 'terisi';
                    Storage::disk('local')->put('parking_lot.json', json_encode($exsistingPark));
                    break;
                }
            }

            $message['plat_nomor'] = $inputData['plat_nomor'];
            $message['parking_lot'] = $inputData['parking_lot'];
            $message['tanggal_masuk'] = $inputData['tanggal_masuk'];
        }

        return $message;
    }

    function setParkingLotSize(Request $request)
    {
        $exsisting = [];

        $count = 0;
        $kapasitas = $request['kapasitas'];
        $inputData = null;
        $message = null;
        for ($i = 0; $i < $kapasitas; $i++) {
            $count += 1;
            $inputData['parking_lot'] = "A" . $count;
            $inputData['status'] = 'kosong';
            array_push($exsisting, $inputData);
            Storage::disk('local')->put('parking_lot.json', json_encode($exsisting));
        }

        $message['message'] = 'Kapasitas parkir telah direset dan diubah menjadi ' . $count;
        return $message;
    }

    function getCar()
    {
        $cars = Storage::disk('local')->exists('car.json') ? json_decode(Storage::disk('local')->get('car.json')) : [];
        return $cars;
    }

    function getCarByType(Request $request)
    {
        $cars = json_decode(Storage::disk('local')->get('car.json'), true);
        $countType = 0;
        foreach ($cars as $car => $value) {
            if ($cars[$car]['tipe'] == $request['tipe']) {
                $countType += 1;
            }
        }

        $message['jumlah_kendaraan'] = $countType;
        return $message;
    }

    function getCarByColor(Request $request)
    {
        $cars = json_decode(Storage::disk('local')->get('car.json'), true);
        $plat = [];
        foreach ($cars as $car => $value) {
            if ($value['warna'] == $request['warna']) {
                array_push($plat, $value['plat_nomor']);
            }
        }

        $message['plat_nomor'] = $plat;
        return $message;
    }

    function getParkSize()
    {
        $parks = Storage::disk('local')->exists('parking_lot.json') ? json_decode(Storage::disk('local')->get('parking_lot.json')) : [];
        return $parks;
    }

    function carExit(Request $request)
    {
        $exsistingCar = json_decode(Storage::disk('local')->get('car.json'), true);
        $exsistingPark = json_decode(Storage::disk('local')->get('parking_lot.json'), true);

        $parking_lot = null;
        $plat_nomor = null;
        $tanggal_masuk = null;
        $jumlah_bayar = null;
        $tipe = null;
        date_default_timezone_set("Asia/Jakarta");
        $tanggal_keluar = date('Y-m-d H:i:s');

        //Get car value from JSON
        foreach ($exsistingCar as $cars => $value) {
            if ($value['plat_nomor'] == $request['plat_nomor']) {
                $parking_lot = $value['parking_lot'];
                $tanggal_masuk = $value['tanggal_masuk'];
                $tipe = $value['tipe'];
                break;
            }
        }

        //Kosongkan value parking lot yang terisi mobil
        foreach ($exsistingPark as $parks => $value) {
            if ($value['parking_lot'] == $parking_lot) {
                $exsistingPark[$parks]['status'] = 'kosong';
                Storage::disk('local')->put('parking_lot.json', json_encode($exsistingPark));
                break;
            }
        }

        $SUVprice = 25000;
        $MPVprice = 35000;
        $lama_parkir = strtotime($tanggal_keluar) - strtotime($tanggal_masuk);
        $jumlah_jam = $lama_parkir / (60 * 60);
        if ($jumlah_jam < 1) {
            switch ($tipe) {
                case 'SUV';
                    $jumlah_bayar = $SUVprice;
                    break;
                case 'MPV';
                    $jumlah_bayar = $MPVprice;
                    break;
            }
        } else {
            if ($tipe == 'SUV') {
                $jumlah_bayar = $SUVprice + (floor($jumlah_jam) * ($SUVprice / 100 * 20));
            } else if ($tipe == 'MPV') {
                $jumlah_bayar = $MPVprice + (floor($jumlah_jam) * ($MPVprice / 100 * 20));
            }
        }

        //Hapus value mobil dalam list JSON
        $messageSukses = null;
        $messageGagal = null;
        $plat_nomor = $request['plat_nomor'];
        $exsist = false;
        foreach ($exsistingCar as $car => $value) {
            if ($value['plat_nomor'] != $plat_nomor) {
                $messageGagal['response'] = 'Plat nomor tidak ditemukan';
            } else if ($value['plat_nomor'] == $plat_nomor) {
                $exsist = true;
                unset($exsistingCar[$car]);
                $cars = array_values($exsistingCar);
                Storage::disk('local')->put('car.json', json_encode($cars));
                $messageSukses['plat_nomor'] = $request['plat_nomor'];
                $messageSukses['tanggal_masuk'] = $tanggal_masuk;
                $messageSukses['tanggal_keluar'] = $tanggal_keluar;
                $messageSukses['jumlah_bayar'] = $jumlah_bayar;
                break;
            }
        }

        if (!$exsist) {
            return $messageGagal;
        } else {
            return $messageSukses;
        }

    }
}