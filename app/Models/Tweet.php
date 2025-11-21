<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'content',
        'media_url',
        'media_type',
        'parent_tweet_id',
        'like_count',
        'repost_count',
        'comment_count',
        'view_count'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    //retweeted
    public function parentTweet(){
        return $this->belongsTo(Tweet::class,'parent_tweet_id');
    }

    //replies
    public function replies(){
        return $this->hasMany(Tweet::class,'parent_tweet_id');
    }

    //likes
    public function likes(){
        return $this->hasMany(Like::class);
    }

    //reposts
    public function reposts(){
        return $this->hasMany(Repost::class);
    }
}
