<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repost extends Model
{
    
    protected $fillable = ['tweet_id', 'user_id'];
}
