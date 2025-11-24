<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TweetController;

Route::get('/test', function () {
    return response()->json(['message' => 'API working']);
});

Route::post('/auth/register',[AuthController::class, 'register']);
Route::post('/auth/login',[AuthController::class, 'login']);

//tweet wiil be authentiacted 
Route::middleware('jwt.auth')->group(function (){
    Route::post('/tweet',[TweetController::class,'createTweet']);
    Route::get('/tweets',[TweetController::class,'getFeed']);
    Route::get('/tweet/{id}',[TweetController::class,'getTweet']);

    //delete tweet
    Route::delete('/tweet/{id}/delete',[TweetController::class,'deleteTweet']);

    //like and unlike routes
    Route::post('/tweet/{id}/like',[TweetController::class,'likeTweet']);
    Route::post('/tweet/{id}/unlike',[TweetController::class,'unlikeTweet']);

    //repost
    Route::post('/tweet/{id}/repost',[TweetController::class,'retweet']);

    //reply
    Route::post('tweet/{id}/reply',[TweetController::class,'replyTweet']);
});