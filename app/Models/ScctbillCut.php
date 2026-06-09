<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScctbillCut extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = 'scctbill_cut';
    protected $primaryKey = 'ID';

    public $timestamps = false;
    protected $fillable = [
        'ID',
        'AA',
        'BILLNM',
        'BTA',
        'BILLCD',
        'BILLAM',
        'BILL_CUT',
        'CUT_DATE',
        'REASON',
        'CREATED_AT',
        'USER_ID'
    ];
}
