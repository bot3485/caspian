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

public function update(ProfileUpdateRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
{
    $user = $request->user();
    $data = $request->safe()->only(['name', 'email']);

    // Обновляем имя и email только если они пришли в запросе
    if (!empty($data)) {
        $user->fill($data);
        
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    }

    // Сохраняем массив интересов, только если они были переданы
    if ($request->has('interests')) {
        $user->interests = $request->input('interests', []);
    }

    // Сохраняем target_country, только если оно передано
    if ($request->has('target_country')) {
        $user->target_country = $request->input('target_country');
    }

    $user->save();

    // Если это AJAX-запрос из рулетки, возвращаем чистый JSON 200 OK
    if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

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