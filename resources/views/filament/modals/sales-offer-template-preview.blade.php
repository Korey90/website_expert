<div class="p-2">
    @if($body)
        <div class="prose prose-sm max-w-none dark:prose-invert">
            {!! \Illuminate\Support\Str::markdown($body) !!}
        </div>
    @else
        <p class="text-sm text-gray-400 italic">No content yet.</p>
    @endif
</div>
