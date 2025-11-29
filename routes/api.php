<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TweetController;
use App\Http\Controllers\FollowController;

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

    //get all repies
    Route::get('tweet/{id}/replies',[TweetController::class,'getReplies']);

    //update tweet
    Route::put('tweet/{id}/update',[TweetController::class,'editTweet']);

    //bookmark
    Route::post('/tweet/{id}/bookmark',[TweetController::class,'bookmarkTweet']);
    Route::delete('/tweet/{id}/bookmark',[TweetController::class,'removeBookmark']);
    Route::get('/bookmarks',[TweetController::class,'getBookmarks']);

    //folow system
    Route::post('/follow/{id}', [FollowController::class, 'follow']);
    Route::post('/unfollow/{id}', [FollowController::class, 'unfollow']);
    Route::get('/followers/{id}', [FollowController::class, 'followers']);
    Route::get('/following/{id}', [FollowController::class, 'following']);
});