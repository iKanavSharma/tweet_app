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
            'media_url'=>$mediaUrl,
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

        //if tweet is reply so derement count 
        if($tweet->parent_tweet_id){
            $parent=Tweet::find($tweet->parent_tweet_id);
            if($parent){
                $parent->decrement('comment_count');
            }
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

    //reply to the tweet
    public function replyTweet(Request $request,$id){
        //check if the tweet to whom we have to reply exist or not
        $parentTweet=Tweet::findOrFail($id);

        $request->validate([
            'content'=>'required_without:media|string|max:280',
            'media'=>'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,webm|max:20480',
        ]);
        $mediaUrl=null;
        $mediaType=null;
        //file upload

        if($request->hasFile('media')){
            $file=$request->file('media');

            //store file
            $path=$file->store('public/tweets');
            $mediaUrl=str_replace('public/','storage/',$path);

            $mime=$file->getMimeType();

            if(str_starts_with($mime,'image/')){

                $mediaType=$file->getClientOriginalExtension()==='gif'?'gif':'image';
            }elseif(str_starts_with($mime,'video/')){
                $mediaType='video';
            }
        }

        //create reply tweet
        $reply=Tweet::create([
            'user_id'=>Auth::id(),
            'content'=>$request->content,
            'media_url'=>$mediaUrl,
            'media_type'=>$mediaType,
            'parent_tweet_id'=>$id,
            'visibility'=>'public',
            'can_reply'=> 'everyone',
            'like_count'=>0,
            'repost_count'=>0,
            'comment_count'=>0,
            'view_count'=>0
        ]);

        $parentTweet->increment('comment_count');

        $reply->load(['user','parentTweet']);

        return response()->json([
            'message'=>'Reply posted successfully',
            'reply'=>$reply,
        ],201);
    }

    //get all replies
    public function getReplies($id){
        $parentTweet=Tweet::findOrFail($id);

        //all replies
        $replies=Tweet::where('parent_tweet_id',$id)->with(['user'])->orderBy('created_at','DESC')->get();

        return response()->json([
            'parent_tweet_id'=>$id,
            'total_replies'=>$replies->count(),
            "replies"=>$replies
        ]);
    }

    //edit tweet
    public function editTweet(Request $request,$id){
        $tweet=Tweet::findOrFail($id);

        //tweet does not match with authorised user
        if($tweet->user_id!==Auth::id()){
            return response()->json(['error'=>'Unauthorized Access'],403);
        }

        $request->validate([
            'content'=>'required_without:media|string|max:280',
            'media'=>'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov,webm|max:20480',
        ]);

        $mediaUrl=$tweet->media_url;
        $mediaType=$tweet->media_type;

        if($request->hasFile('media')){
            $file=$request->file('media');
            $path=$file->store('public/tweets');

            $mediaUrl=str_replace('public/','storage/',$path);

            $mime=$file->getMimeType();
            if(str_starts_with($mime,'image/')){
                $mediaType=$file->getClientOriginalExtension()==='gif'?'gif':'image';
            }elseif(str_starts_with($mime,'video/')){
                $mediaType='video';
            }
        }

        //update tweet
        $tweet->update([
            'content'=>$request->content,
            'media_url'=>$mediaUrl,
            'media_type'=>$mediaType,
        ]);

        return response()->json([
            'message'=>'Tweet edited Successfully',
            'tweet'=>$tweet
        ]);

    }

    
    //bookmark
    public function bookmarkTweet($id){
        $tweet = Tweet::findOrFail($id);

        // Check if already bookmarked
        if (\DB::table('bookmarks')->where('user_id', Auth::id())->where('tweet_id', $id)->exists()) {
            return response()->json(['message' => 'Already bookmarked'], 409);
        }

        \DB::table('bookmarks')->insert([
            'user_id' => Auth::id(),
            'tweet_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Tweet bookmarked successfully']);
    }

    //remove bookmark
    public function removeBookmark($id){
        $deleted = \DB::table('bookmarks')
            ->where('user_id', Auth::id())
            ->where('tweet_id', $id)
            ->delete();

        if(!$deleted){
            return response()->json(['message' => 'Bookmark not found'], 404);
        }

        return response()->json(['message' => 'Bookmark removed successfully']);
    }


    //get all bookmark
    public function getBookmarks(){
        $bookmarkedTweets = Tweet::whereIn('id', function($query){
            $query->select('tweet_id')
                ->from('bookmarks')
                ->where('user_id', Auth::id());
        })->with(['user','replies'])->orderBy('created_at','DESC')->get();

        return response()->json($bookmarkedTweets);
    }

}
