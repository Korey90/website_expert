@php
    $phases = $record->phases()->orderBy('order')->get();

    $statusMeta = [
        'pending'     => ['label' => 'Pending',     'bg' => 'rgba(255,255,255,0.03)', 'border' => 'rgba(255,255,255,0.08)', 'left' => '#64748b'],
        'in_progress' => ['label' => 'In Progress', 'bg' => 'rgba(251,191,36,0.07)',  'border' => 'rgba(251,191,36,0.25)',  'left' => '#fbbf24'],
        'completed'   => ['label' => 'Completed',   'bg' => 'rgba(74,222,128,0.07)',  'border' => 'rgba(74,222,128,0.25)',  'left' => '#4ade80'],
        'cancelled'   => ['label' => 'Cancelled',   'bg' => 'rgba(248,113,113,0.07)', 'border' => 'rgba(248,113,113,0.25)', 'left' => '#f87171'],
    ];

    $total = $phases->count();
    $done  = $phases->where('status', 'completed')->count();
    $pct   = $total > 0 ? round($done / $total * 100) : 0;
@endphp

@if($phases->isEmpty())
    <div style="text-align:center;padding:32px 0;color:#64748b;">
        <p style="margin:0;font-size:14px;">No phases yet.</p>
        <p style="margin:4px 0 0;font-size:13px;">Apply a project template or create phases from the Edit page.</p>
    </div>
@else
    {{-- Overall progress bar --}}
    <div style="margin-bottom:16px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
            <span style="font-size:13px;font-weight:600;color:#cbd5e1;">Overall Phase Progress</span>
            <span style="font-size:13px;font-weight:700;color:#818cf8;">{{ $pct }}%</span>
        </div>
        <div style="background:rgba(255,255,255,0.08);border-radius:6px;height:8px;">
            <div style="background:linear-gradient(90deg,#818cf8,#a78bfa);border-radius:6px;height:8px;width:{{ $pct }}%;transition:width .3s;"></div>
        </div>
    </div>

    {{-- Phase cards --}}
    <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($phases as $phase)
            @php
                $meta     = $statusMeta[$phase->status] ?? $statusMeta['pending'];
                $tasks    = $phase->tasks()->count();
                $taskDone = $phase->tasks()->where('status', 'done')->count();
                $taskPct  = $tasks > 0 ? round($taskDone / $tasks * 100) : 0;

                $btnDefs = [
                    'pending'     => ['label' => 'Pending',     'dot' => '#64748b', 'bg' => 'rgba(255,255,255,0.08)',  'border' => 'rgba(255,255,255,0.2)'],
                    'in_progress' => ['label' => 'In Progress', 'dot' => '#fbbf24', 'bg' => 'rgba(251,191,36,0.15)',  'border' => 'rgba(251,191,36,0.4)'],
                    'completed'   => ['label' => 'Completed',   'dot' => '#4ade80', 'bg' => 'rgba(74,222,128,0.15)',  'border' => 'rgba(74,222,128,0.4)'],
                    'cancelled'   => ['label' => 'Cancelled',   'dot' => '#f87171', 'bg' => 'rgba(248,113,113,0.15)', 'border' => 'rgba(248,113,113,0.4)'],
                ];
            @endphp

            <div style="background:{{ $meta['bg'] }};border:1px solid {{ $meta['border'] }};border-left:4px solid {{ $meta['left'] }};border-radius:8px;padding:14px 18px;">
                {{-- Phase header --}}
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                        <span style="flex-shrink:0;background:rgba(255,255,255,0.1);color:#e2e8f0;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;">{{ $phase->order }}</span>
                        <span style="font-weight:600;font-size:14px;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $phase->name }}</span>
                    </div>
                    @if($tasks > 0)
                        <span style="flex-shrink:0;font-size:12px;color:#94a3b8;background:rgba(255,255,255,0.06);border-radius:12px;padding:2px 8px;">{{ $taskDone }}/{{ $tasks }} tasks</span>
                    @else
                        <span style="flex-shrink:0;font-size:12px;color:#475569;">No tasks</span>
                    @endif
                </div>

                @if($phase->description)
                    <p style="margin:6px 0 0;font-size:13px;color:#64748b;line-height:1.5;">{{ $phase->description }}</p>
                @endif

                {{-- Task progress bar --}}
                @if($tasks > 0)
                    <div style="margin-top:10px;background:rgba(255,255,255,0.08);border-radius:4px;height:4px;">
                        <div style="background:{{ $meta['left'] }};border-radius:4px;height:4px;width:{{ $taskPct }}%;transition:width .3s;"></div>
                    </div>
                @endif

                {{-- Status buttons --}}
                <div style="display:flex;gap:5px;margin-top:10px;flex-wrap:wrap;">
                    @foreach($btnDefs as $status => $btn)
                        @php
                            $isActive = $phase->status === $status;
                        @endphp
                        <button
                            wire:click="updatePhaseStatus({{ $phase->id }}, '{{ $status }}')"
                            wire:loading.attr="disabled"
                            wire:target="updatePhaseStatus({{ $phase->id }}, '{{ $status }}')"
                            type="button"
                            style="cursor:pointer;background:{{ $isActive ? $btn['bg'] : 'transparent' }};border:1px solid {{ $isActive ? $btn['border'] : 'rgba(255,255,255,0.07)' }};border-radius:20px;padding:3px 11px;font-size:11px;font-weight:600;color:{{ $isActive ? $btn['dot'] : '#475569' }};letter-spacing:.3px;line-height:1.6;transition:all .15s;"
                        >
                            @if($isActive)
                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:{{ $btn['dot'] }};margin-right:5px;vertical-align:middle;"></span>
                            @endif
                            {{ $btn['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
