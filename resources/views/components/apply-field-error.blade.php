@props(['name'])

<p
    x-cloak
    x-show="messageFor('{{ $name }}')"
    class="mt-2 flex items-start gap-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-medium leading-5 text-red-800 ring-1 ring-inset ring-red-200"
>
    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-4a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
    </svg>
    <span x-text="messageFor('{{ $name }}')"></span>
</p>
