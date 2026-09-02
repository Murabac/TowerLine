<x-app-layout>
    @php
        $firstGroup = $groups->keys()->first();
        $allTitles = $groups->flatten()->map->title()->values();
    @endphp

    <div
        class="p-4 lg:p-8"
        x-data="{
            query: '',
            group: @js($firstGroup),
            allTitles: @js($allTitles),
            searching() {
                return this.query.trim() !== '';
            },
            matches(title, groupKey) {
                const q = this.query.trim().toLowerCase();
                if (q) {
                    return title.toLowerCase().includes(q);
                }
                return this.group === groupKey;
            },
            sectionVisible(titles, groupKey) {
                const q = this.query.trim().toLowerCase();
                if (q) {
                    return titles.some((title) => title.toLowerCase().includes(q));
                }
                return this.group === groupKey;
            },
            get noMatches() {
                const q = this.query.trim().toLowerCase();
                return q !== '' && ! this.allTitles.some((title) => title.toLowerCase().includes(q));
            },
        }"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-6">
            <div class="max-w-xl">
                <h1 class="text-2xl font-semibold tracking-tight text-brand">{{ __('app.reports.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('app.reports.subtitle') }}</p>
            </div>
            @if ($groups->isNotEmpty())
                <label class="block w-full sm:w-72">
                    <span class="sr-only">{{ __('app.reports.search') }}</span>
                    <input type="search" x-model="query" placeholder="{{ __('app.reports.search_placeholder') }}" class="field w-full">
                </label>
            @endif
        </div>

        @if ($groups->isEmpty())
            <p class="text-sm text-gray-500">{{ __('app.reports.empty_hub') }}</p>
        @else
            <nav class="rounded-2xl border border-gray-200 bg-white p-2" x-show="! searching()" aria-label="{{ __('app.reports.title') }}">
                <div class="flex flex-wrap gap-2">
                    @foreach ($groups as $tab => $tabReports)
                        <button
                            type="button"
                            @click="group = @js($tab)"
                            class="rounded-xl px-4 py-2 text-sm font-medium transition"
                            :class="group === @js($tab) ? 'bg-brand text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-brand'"
                        >
                            {{ __('app.reports.groups.'.$tab) }}
                        </button>
                    @endforeach
                </div>
            </nav>

            <div class="mt-6">
                @foreach ($groups as $group => $reports)
                    <section x-show="sectionVisible(@js($reports->map->title()->values()), @js($group))" x-cloak>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400" x-show="searching()">
                            {{ __('app.reports.groups.'.$group) }}
                        </p>
                        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($reports as $item)
                                <a
                                    href="{{ route('reports.show', $item->key) }}"
                                    x-show="matches(@js($item->title()), @js($group))"
                                    class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04)] hover:border-brand hover:shadow-md transition"
                                >
                                    <p class="font-semibold text-gray-900 group-hover:text-brand">{{ $item->title() }}</p>
                                    @if ($item->summary())
                                        <p class="mt-1.5 text-sm leading-6 text-gray-500">{{ $item->summary() }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <p class="py-16 text-center text-sm text-gray-500" x-show="noMatches">{{ __('app.reports.empty_search') }}</p>
            </div>
        @endif
    </div>
</x-app-layout>
