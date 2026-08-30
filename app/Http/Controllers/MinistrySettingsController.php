<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMinistrySettingsRequest;
use App\Models\MinistrySetting;
use App\Support\Audits;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MinistrySettingsController extends Controller
{
    public function edit(): View
    {
        $settings = MinistrySetting::current();
        $this->authorize('update', $settings);

        return view('settings.ministry', [
            'settings' => $settings,
            'director' => \App\Support\MinistrySettings::approvalLetterDirector(),
        ]);
    }

    public function update(UpdateMinistrySettingsRequest $request): RedirectResponse
    {
        $settings = MinistrySetting::current();
        $this->authorize('update', $settings);

        $settings->update($request->validated());
        Audits::log('updated', $settings, $settings->only([
            'approval_director_name',
            'approval_director_title_so',
            'approval_director_title_en',
        ]));

        return redirect()
            ->route('settings.ministry.edit')
            ->with('status', __('app.settings.saved'));
    }
}
