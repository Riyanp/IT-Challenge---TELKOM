<?php
/**
 * Created by PhpStorm.
 * User: d.adelekan
 * Date: 24/08/2016
 * Time: 01:57
 */

namespace App\Repository\Transformers;


class ParkTransformer extends Transformer{

    public function transform($park){

        return [
            'fullname' => $park->plat_nomor,
            'email' => $park->warna,
            'api_token' => $park->tipe,
        ];

    }

}