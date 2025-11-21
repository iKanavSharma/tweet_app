<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tweet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Like;
use App\Models\Repost;
use Validator;

class TweetController extends Controller
{
    //create tweet
    public function createTweet(Request $request){

        $request->validate([
            'content'=>'required|string|max:280',
            'media_url'=>'nullable|string',
            'media_type'=>'nullable|in:image,video,gif',
            'parent_tweet_id'=>'nullable|exists:tweets,id',
            'visibility'=>'nullable|in:public,private,followers',
            'can_reply'=>'nullable|in:everyone,followers,mentioned'
        ]);

        $mediaUrl=null;

        if($request->hasFile('media')){
            
            $path=$request->file('media')->store('public/tweets');
            $mediaUrl=str_replace('public/','storage/',$path);
        }

        //tweet
        $tweet=Tweet::create([
            'user_id'=>Auth::id(),
            'content'=>$request->content,
            'media_url'=>$request->media_url,
            'media_type'=>$request->media_type,
            'parent_tweet_id'=>$request->parent_tweet_id,
            'visibility'=>$request->visibility ?? 'public',
            'can_reply'=>$request->can_reply ?? 'everyone',
            'like_count'=>0,
            'repost_count'=>0,
            'comment_count'=>0,
            'view_count'=>0
        ]);

        return response()->json([
            'message'=>'Tweet Posted Successfully',
            'tweet'=>$tweet
        ],201);
    }

    //get all tweets of a user
    public function getFeed(){
        $tweets=Tweet::with(['user','replies'])->orderBy('created_at','DESC')->get();

        return response()->json($tweets);
    }

    //get a single tweet and increse veiw(trough id)
    public function getTweet($id){
        $tweet=Tweet::with(['user','replies'])->findOrFail($id);

        //count increase
        $tweet->increment('view_count');

        return response()->json($tweet);
    }

    //delete
    public function deleteTweet($id){
        $tweet=Tweet::findOrFail($id);

        if($tweet->user_id!==Auth::id()){
            return response()->json(['error'=>'Unauthorized Access'],403);
        }

        $tweet->delete();

        return response()->json(['message'=>'Tweet Deleted Successfully']);
    }

    //like
    public function likeTweet($id){
        $tweet=Tweet::findOrFail($id);

        //if alraedy liked
        if(Like::where('user_id',Auth::id())->where('tweet_id',$id)->exists()){
            return response()->json(['message'=>'Already Liked'],409);
        }

        Like::create([
            'tweet_id'=>$id,
            'user_id'=>Auth::id(),
        ]);

        $tweet->increment('like_count');

        return response()->json(['message'=>'Tweet liked']);
    }

    //unlike
    
    public function unlikeTweet($id){
        $tweet=Tweet::findOrFail($id);

        //if alraedy liked
        $like=Like::where('tweet_id',$id)->where('user_id',Auth::id())->first();

        if(!$like){
            return response()->json(['message'=>'Not liked yet'],409);
        }
        

        $like->delete();
        $tweet->decrement('like_count');

        return response()->json(['message'=>'Tweet unliked']);
    }

    //repost
    //same tweet is posted
    public function retweet($id){
        $tweet=Tweet::findOrFail($id);

        //check whther tweet has been reposted or not
        if (Repost::where('user_id', Auth::id())->where('tweet_id', $id)->exists()) {
            return response()->json(['message' => 'Already Reposted'], 409);
        }
        Repost::create([
            'tweet_id'=>$id,
            'user_id'=>Auth::id(),
        ]);

        $tweet->increment('repost_count');

        return response()->json(['message'=>'Tweet Resposted']);
    }
}
