<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Redirect, DB};
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = Auth::user();
        $friendsCount = DB::table('contacts')->where('user_id', $user->id)->count();
        
        // Получаем интересы и ГАРАНТИРУЕМ, что это строка для инпута
        $interests = $user->interests;
        
        // Если прилетела строка (ошибка каста), декодируем. Если null - пустой массив.
        if (is_string($interests)) {
            $interests = json_decode($interests, true) ?? [];
        }
        
        $interestsString = is_array($interests) ? implode(', ', $interests) : '';
        
        return view('profile.edit', [
            'user' => $user,
            'friendsCount' => $friendsCount,
            'interestsString' => $interestsString
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = User::find(Auth::id());

        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Превращаем строку "A, B, C" в массив ["A", "B", "C"]
        $raw = $request->input('interests_string', '');
        if (!empty(trim($raw))) {
            $tags = array_filter(array_map('trim', explode(',', $raw)));
            $user->interests = array_values($tags);
        } else {
            $user->interests = [];
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);
        $user = Auth::user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('/');
    }
}