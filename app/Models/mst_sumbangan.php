<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mst_sumbangan extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = "mst_sumbangan";

    protected $primaryKey = "idincrement";

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        "namaSumbangan",
        "STCUST",
        "NOCUST",
    ];
}
