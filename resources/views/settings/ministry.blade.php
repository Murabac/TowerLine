<x-app-layout>
    <div class="p-4 lg:p-8 max-w-3xl">
        <div class="rounded-2xl bg-brand text-white px-5 py-5 sm:px-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-light">{{ __('app.settings.title') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ __('app.settings.ministry_title') }}</h1>
            <p class="mt-2 text-sm text-white/75">{{ __('app.settings.ministry_subtitle') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.ministry.update', $settings) }}" class="mt-5 space-y-5">
            @csrf
            @method('PUT')

            <section class="data-card p-5 sm:p-6 space-y-5">
                <div>
                    <h2 class="text-sm font-semibold text-brand">{{ __('app.settings.approval_letter_section') }}</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.settings.approval_letter_hint') }}</p>
                </div>

                <div>
                    <x-input-label for="approval_director_name" :value="__('app.settings.director_name')" />
                    <x-text-input id="approval_director_name" name="approval_director_name" type="text" class="mt-1 block w-full"
                        :value="old('approval_director_name', $settings->approval_director_name)" />
                    <p class="mt-1 text-xs text-gray-500">{{ __('app.settings.director_name_hint') }}</p>
                    <x-input-error :messages="$errors->get('approval_director_name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="approval_director_title_so" :value="__('app.settings.director_title_so')" />
                    <x-text-input id="approval_director_title_so" name="approval_director_title_so" type="text" class="mt-1 block w-full" required
                        :value="old('approval_director_title_so', $settings->approval_director_title_so)" />
                    <x-input-error :messages="$errors->get('approval_director_title_so')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="approval_director_title_en" :value="__('app.settings.director_title_en')" />
                    <x-text-input id="approval_director_title_en" name="approval_director_title_en" type="text" class="mt-1 block w-full" required
                        :value="old('approval_director_title_en', $settings->approval_director_title_en)" />
                    <x-input-error :messages="$errors->get('approval_director_title_en')" class="mt-1" />
                </div>
            </section>

            <section class="data-card p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-brand">{{ __('app.settings.preview') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ __('app.settings.preview_hint') }}</p>
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-5 text-center">
                    @if (filled($director['name']))
                        <p class="text-base font-semibold text-brand">{{ $director['name'] }}</p>
                    @else
                        <p class="text-sm italic text-gray-400">{{ __('app.settings.director_name_missing') }}</p>
                    @endif
                    <p class="mt-1 text-sm font-semibold text-gray-800">{{ $director['title_so'] }}</p>
                    <p class="text-sm text-gray-600">{{ $director['title_en'] }}</p>
                    <p class="mt-6 border-t border-dashed border-gray-300 pt-3 text-xs text-gray-400">{{ __('app.approval_letters.signature_placeholder') }}</p>
                </div>
            </section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button type="submit" class="inline-flex items-center justify-center px-5 py-3 bg-brand text-white text-sm font-semibold rounded-2xl hover:bg-brand-dark shadow-sm">
                    {{ __('app.save') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
