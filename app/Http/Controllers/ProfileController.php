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
        $interests = $user->interests ?? [];
        $interestsString = is_array($interests) ? implode(', ', $interests) : '';
        
        return view('profile.edit', [
            'user' => $user,
            'friendsCount' => $friendsCount,
            'interestsString' => $interestsString
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Обработка интересов
        $raw = $request->input('interests_string', '');
        if (!empty(trim($raw))) {
            // Очищаем от пробелов и пустых элементов
            $tags = array_filter(array_map('trim', explode(',', $raw)));
            $user->interests = array_values($tags);
        } else {
            $user->interests = []; // Всегда массив
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