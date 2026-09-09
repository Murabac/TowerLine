<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StoreUserSignatureRequest;
use App\Support\UserSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function storeSignature(StoreUserSignatureRequest $request): RedirectResponse
    {
        $png = UserSignature::binaryFromRequest($request->user(), $request);
        abort_unless($png, 422);
        UserSignature::storeForUser($request->user(), $png);

        return Redirect::route('profile.edit')->with('status', __('app.signatures.saved'));
    }

    public function destroySignature(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (UserSignature::exists($user->signature_path)) {
            Storage::disk('local')->delete($user->signature_path);
        }

        $user->forceFill(['signature_path' => null])->save();

        return Redirect::route('profile.edit')->with('status', __('app.signatures.cleared'));
    }

    public function showSignature(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasSavedSignature(), 404);

        return Storage::disk('local')->response($request->user()->signature_path, 'signature.png', [
            'Content-Type' => 'image/png',
        ]);
    }
}
