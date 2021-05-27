<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(Payment::class);
    }

}
