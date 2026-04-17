<?php

namespace App\Repositories\Account;

use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EloquentAccount extends EloquentRepository implements AccountRepository, BaseRepository
{
    public function profile()
    {
        return Auth::user();
    }

    public function updateProfile(Request $request)
    {
        return Auth::user()->update($request->all());
    }

    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        if ($request->hasFile('image')) {
            $user->saveImage($request->file('image'), 'avatar');
        }

        return $user;
    }

    public function deletePhoto(Request $request)
    {
        $user = Auth::user();

        $user->deleteImage();

        return $user;
    }

    public function updatePassword(Request $request)
    {
        return $request->user()->fill([
            'password' => $request->input('password'),
        ])->save();
    }

    public function delete(Request $request)
    {
        $user = $request->user();

        $user->flushAddresses();

        $user->flushImages();

        return $user->delete();
    }
}
