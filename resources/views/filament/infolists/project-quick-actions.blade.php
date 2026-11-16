@php
    $status      = $record->status;
    $taskBoardUrl = \App\Filament\Resources\ProjectResource::getUrl('tasks', ['record' => $record]);

    $rowBase = 'width:100%;display:flex;align-items:center;gap:10px;padding:9px 12px;'
             . 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);'
             . 'border-radius:8px;cursor:pointer;text-align:left;'
             . 'font-size:13px;font-weight:500;transition:background .15s;';
@endphp

<div style="display:flex;flex-direction:column;gap:6px;">

    {{-- Task Board (always visible) --}}
    <a href="{{ $taskBoardUrl }}"
       style="{{ $rowBase }} color:#94a3b8;text-decoration:none;"
       onmouseover="this.style.background='rgba(255,255,255,0.08)'"
       onmouseout="this.style.background='rgba(255,255,255,0.04)'">
        <svg style="width:15px;height:15px;flex-shrink:0;color:#64748b;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
        </svg>
        <span style="color:#cbd5e1;">Task Board</span>
    </a>

    {{-- Mark Active (draft or on_hold) --}}
    @if(in_array($status, ['draft', 'on_hold']))
        <button wire:click="updateProjectStatus('active')"
                type="button"
                style="{{ $rowBase }} color:#4ade80;border-color:rgba(74,222,128,0.15);"
                onmouseover="this.style.background='rgba(74,222,128,0.08)'"
                onmouseout="this.style.background='rgba(255,255,255,0.04)'">
            <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
            </svg>
            <span>Mark Active</span>
        </button>
    @endif

    {{-- Put On Hold (active only) --}}
    @if($status === 'active')
        <button wire:click="updateProjectStatus('on_hold')"
                type="button"
                x-on:click="if(!confirm('Put this project on hold?')) { $event.preventDefault(); $event.stopImmediatePropagation(); }"
                style="{{ $rowBase }} color:#fbbf24;border-color:rgba(251,191,36,0.15);"
                onmouseover="this.style.background='rgba(251,191,36,0.08)'"
                onmouseout="this.style.background='rgba(255,255,255,0.04)'">
            <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" />
            </svg>
            <span>Put On Hold</span>
        </button>
    @endif

    {{-- Mark Complete (active or on_hold) --}}
    @if(in_array($status, ['active', 'on_hold']))
        <button wire:click="updateProjectStatus('completed')"
                type="button"
                x-on:click="if(!confirm('Mark project as completed?')) { $event.preventDefault(); $event.stopImmediatePropagation(); }"
                style="{{ $rowBase }} color:#818cf8;border-color:rgba(129,140,248,0.15);"
                onmouseover="this.style.background='rgba(129,140,248,0.08)'"
                onmouseout="this.style.background='rgba(255,255,255,0.04)'">
            <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>Mark Complete</span>
        </button>
    @endif

    {{-- Cancel Project (not already cancelled/completed) --}}
    @if(!in_array($status, ['cancelled', 'completed']))
        <button wire:click="updateProjectStatus('cancelled')"
                type="button"
                x-on:click="if(!confirm('Cancel this project? This can be undone later.')) { $event.preventDefault(); $event.stopImmediatePropagation(); }"
                style="{{ $rowBase }} color:#f87171;border-color:rgba(248,113,113,0.15);"
                onmouseover="this.style.background='rgba(248,113,113,0.08)'"
                onmouseout="this.style.background='rgba(255,255,255,0.04)'">
            <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>Cancel Project</span>
        </button>
    @endif

</div>
