<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class u_daftar_harga extends Model
{
    protected $connection = "DATA_MYSQL";

    public $timestamps = false;

    public $incrementing = false;

    protected $table = "u_daftar_harga";

    protected $primaryKey = "urut";

    protected $fillable = [
        "kode_fak",
        "kode_prod",
        "KodeAkun",
        "NamaAkun",
        "thn_masuk",
        "nominal",
        "NoRek",
    ];
}