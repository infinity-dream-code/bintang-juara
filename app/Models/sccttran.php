<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sccttran extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = "sccttran";

    protected $primaryKey = "urut";

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        "CUSTID",
        "METODE",
        "TRXDATE",
        "NOREFF",
        "FIDBANK",
        "KDCHANNEL",
        "DEBET",
        "KREDIT",
        "REFFBANK",
        "TRANSNO",
    ];
}
