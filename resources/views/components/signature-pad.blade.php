@props(['savedUrl' => null])

@php
    $hasSaved = filled($savedUrl);
@endphp

<div
    class="space-y-3"
    x-data="{
        hasSaved: {{ $hasSaved ? 'true' : 'false' }},
        useSaved: {{ $hasSaved ? 'true' : 'false' }},
        drawing: false,
        drawn: false,
        init() {
            const canvas = this.$refs.pad
            const ctx = canvas.getContext('2d')
            ctx.strokeStyle = '#111827'
            ctx.lineWidth = 2.2
            ctx.lineCap = 'round'
            ctx.lineJoin = 'round'
            const point = (event) => {
                const rect = canvas.getBoundingClientRect()
                return {
                    x: (event.clientX - rect.left) * (canvas.width / rect.width),
                    y: (event.clientY - rect.top) * (canvas.height / rect.height),
                }
            }
            const start = (event) => {
                if (this.useSaved) {
                    return
                }
                event.preventDefault()
                this.drawing = true
                this.drawn = true
                const p = point(event)
                ctx.beginPath()
                ctx.moveTo(p.x, p.y)
            }
            const move = (event) => {
                if (! this.drawing || this.useSaved) {
                    return
                }
                event.preventDefault()
                const p = point(event)
                ctx.lineTo(p.x, p.y)
                ctx.stroke()
            }
            const end = () => {
                this.drawing = false
                if (this.drawn && ! this.useSaved) {
                    this.$refs.data.value = canvas.toDataURL('image/png')
                }
            }
            canvas.addEventListener('pointerdown', start)
            canvas.addEventListener('pointermove', move)
            window.addEventListener('pointerup', end)
        },
        clearPad() {
            const canvas = this.$refs.pad
            const ctx = canvas.getContext('2d')
            ctx.clearRect(0, 0, canvas.width, canvas.height)
            this.drawn = false
            this.$refs.data.value = ''
        }
    }"
>
    <input type="hidden" name="use_saved_signature" :value="useSaved ? '1' : '0'">
    <input type="hidden" name="signature_data" x-ref="data" value="">

    <p class="text-sm font-medium text-gray-700">{{ __('app.signatures.label') }}</p>
    <p class="text-xs text-gray-500">{{ __('app.signatures.hint') }}</p>

    @if ($hasSaved)
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="useSaved = true" @class(['inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold'])
                    :class="useSaved ? 'bg-brand text-white' : 'bg-gray-100 text-gray-700'">
                {{ __('app.signatures.use_saved') }}
            </button>
            <button type="button" @click="useSaved = false; clearPad()" @class(['inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold'])
                    :class="! useSaved ? 'bg-brand text-white' : 'bg-gray-100 text-gray-700'">
                {{ __('app.signatures.draw_new') }}
            </button>
        </div>
        <div x-show="useSaved" class="rounded-lg border border-gray-200 bg-white px-3 py-2">
            <img src="{{ $savedUrl }}" alt="" class="h-16 w-auto max-w-full object-contain">
        </div>
    @endif

    <div x-show="! useSaved" x-cloak>
        <canvas
            x-ref="pad"
            width="560"
            height="180"
            class="w-full touch-none rounded-lg border border-gray-300 bg-white"
            style="touch-action: none;"
        ></canvas>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
            <button type="button" class="text-xs font-semibold text-gray-600 hover:text-brand" @click="clearPad()">
                {{ __('app.signatures.clear') }}
            </button>
            <label class="inline-flex items-center gap-2 text-xs text-gray-600">
                <input type="checkbox" name="save_signature" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" checked>
                {{ __('app.signatures.save_for_later') }}
            </label>
        </div>
    </div>

    @error('signature_data')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
