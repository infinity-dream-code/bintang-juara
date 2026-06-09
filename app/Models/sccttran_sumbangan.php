<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sccttran_sumbangan extends Model
{
    protected $connection = 'DATA_MYSQL';

    protected $table = 'sccttran_sumbangan';

    protected $primaryKey = 'TRANSNO';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'CUSTID',
        'METODE',
        'TRXDATE',
        'DEBET',
        'KREDIT',
    ];
}
