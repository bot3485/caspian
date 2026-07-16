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
    
    // 1. Обработка базовых данных (Имя, Email)
    $data = $request->safe()->only(['name', 'email']);
    if (!empty($data)) {
        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
    }

    // 2. Локаль
    if ($request->has('locale')) {
        $user->locale = $request->input('locale');
    }
    
    // 3. Интересы
    if ($request->has('interests')) {
        $user->interests = $request->input('interests', []);
    }

    // 4. ГЕО-фильтр (Страна поиска)
    if ($request->has('target_country')) {
        $user->target_country = $request->input('target_country');
    }

    // 5. НОВОЕ: Личные данные (Пол и Возраст)
    if ($request->has('gender')) {
        $user->gender = $request->input('gender');
    }
    if ($request->has('age')) {
        $user->age = $request->input('age');
    }

    // 6. НОВОЕ: Фильтры поиска (Таргетинг в рулетке)
    if ($request->has('target_gender')) {
        $user->target_gender = $request->input('target_gender');
    }
    if ($request->has('target_age_min')) {
        $user->target_age_min = (int) $request->input('target_age_min');
    }
    if ($request->has('target_age_max')) {
        $user->target_age_max = (int) $request->input('target_age_max');
    }

    $user->save();

    // Возврат ответа
    if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'status' => 'success',
            'user' => $user->fresh() // fresh() подтянет актуальные данные из БД
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