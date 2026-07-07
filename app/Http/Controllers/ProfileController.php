<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Redirect, DB};
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $friendsCount = DB::table('contacts')->where('user_id', Auth::id())->count();
        
        return view('profile.edit', [
            'user' => Auth::user(),
            'friendsCount' => $friendsCount
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = \App\Models\User::find(Auth::id());

        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $raw = $request->input('interests_string', '');
        if (!empty(trim($raw))) {
            $user->interests = array_values(array_filter(array_map('trim', explode(',', $raw))));
        } else {
            $user->interests = [];
        }

        $user->save();
        
        // ПРИНУДИТЕЛЬНО ОБНОВЛЯЕМ ОБЪЕКТ ИЗ БАЗЫ
        $user->fresh(); 

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