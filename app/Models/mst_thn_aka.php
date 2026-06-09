<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mst_thn_aka extends Model
{
    protected $connection = "DATA_MYSQL";

    protected $table = "mst_thn_aka";

    protected $primaryKey = "urut";

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        "thn_aka",
    ];

    public static function getMstThnAkaAttributes(): array|object
    {
        return static::select(["thn_aka"])
            ->whereNotNull("thn_aka")
            ->distinct()
            ->orderBy("thn_aka", "desc")
            ->get();
    }
}
