<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    // Follow a user
    public function follow($id)
    {
        $userToFollow = User::findOrFail($id);

        if (Auth::id() == $id) {
            return response()->json(['message' => 'Cannot follow yourself'], 400);
        }

        // Check if already following
        if (DB::table('follows')->where('follower_id', Auth::id())->where('following_id', $id)->exists()) {
            return response()->json(['message' => 'Already following'], 409);
        }

        DB::table('follows')->insert([
            'follower_id' => Auth::id(),
            'following_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'User followed successfully']);
    }

    // Unfollow a user
    public function unfollow($id)
    {
        $userToUnfollow = User::findOrFail($id);

        $deleted = DB::table('follows')
            ->where('follower_id', Auth::id())
            ->where('following_id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Not following this user'], 409);
        }

        return response()->json(['message' => 'User unfollowed successfully']);
    }

    // List followers of authenticated user
    public function followers($id)
    {
        $user = User::findOrFail($id);

        $followers = DB::table('follows')
            ->where('following_id', $id)
            ->join('users', 'follows.follower_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email') // adjust fields
            ->get();

        return response()->json($followers);
    }

    // List users that the authenticated user is following
    public function following($id)
    {
        $user = User::findOrFail($id);

        $following = DB::table('follows')
            ->where('follower_id', $id)
            ->join('users', 'follows.following_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email') // adjust fields
            ->get();

        return response()->json($following);
    }
}
