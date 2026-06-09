<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tbl_notice extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = "tbl_notice";

    protected $primaryKey = "idincrement";

    public $timestamps = false;

    protected $fillable = [
        "Title",
        "Payload",
        "Date",
        "Status",
    ];
}
