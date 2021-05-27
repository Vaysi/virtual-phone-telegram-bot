<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TUser extends Model
{
    protected $guarded = [];
    protected $table = 'tusers';

    public function payments(){
        return $this->hasMany(Payment::class,'tuser_id','id');
    }
}
