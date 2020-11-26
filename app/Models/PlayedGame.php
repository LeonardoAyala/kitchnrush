<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayedGame extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'best_time',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
/*
    public function player()
    {
        return $this->hasOne('App\Models\User');
    }
*/
}
