<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable=['title','time','description'];
    public function uesrs(){
        return $this->belongsToMany('App\User');
    }
}
