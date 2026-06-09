<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class u_akun extends Model
{
    protected $connection = "DATA_MYSQL";

    public $timestamps = false;

    public $incrementing = false;

    protected $table = "u_akun";

    protected $primaryKey = "KodeAkun";

    protected $fillable = [
        "KodeAkun",
        "NamaAkun",
        "NoRek",
    ];
}