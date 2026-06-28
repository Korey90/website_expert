<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Briefing Template — {{ $template->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a2e;
            background: #ffffff;
        }

        /* ── Header ───────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            padding: 32px 40px 24px;
            border-bottom: 3px solid #6366f1;
        }
        .header-left  { display: table-cell; width: 65%; vertical-align: top; }
        .header-right { display: table-cell; width: 35%; vertical-align: top; text-align: right; }

        .doc-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        .doc-description {
            margin-top: 8px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ── Badges ──────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .badge-type-discovery      { background: #dbeafe; color: #1d4ed8; }
        .badge-type-qualification  { background: #fef3c7; color: #92400e; }
        .badge-type-proposal_input { background: #dcfce7; color: #166534; }
        .badge-type-sales_offer    { background: #ede9fe; color: #5b21b6; }
        .badge-type-custom         { background: #f1f5f9; color: #475569; }
        .badge-gray                { background: #f1f5f9; color: #475569; }
        .badge-active              { background: #dcfce7; color: #166534; }
        .badge-inactive            { background: #f1f5f9; color: #94a3b8; }

        /* ── Meta row ─────────────────────────────────────── */
        .meta-row {
            padding: 14px 40px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-row table { width: 100%; }
        .meta-row td {
            font-size: 11px;
            color: #64748b;
            padding: 0 16px 0 0;
            vertical-align: middle;
        }
        .meta-row td .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            display: block;
            margin-bottom: 2px;
        }

        /* ── Section ─────────────────────────────────────── */
        .section {
            padding: 24px 40px;
            border-bottom: 1px solid #f1f5f9;
            page-break-inside: avoid;
        }
        .section:last-child { border-bottom: none; }

        .section-heading {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }
        .section-num {
            display: table-cell;
            width: 28px;
            vertical-align: top;
        }
        .section-num-box {
            width: 22px;
            height: 22px;
            background: #eef2ff;
            border-radius: 6px;
            text-align: center;
            line-height: 22px;
            font-size: 11px;
            font-weight: 700;
            color: #6366f1;
        }
        .section-title {
            display: table-cell;
            vertical-align: middle;
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .section-key {
            font-size: 9px;
            color: #94a3b8;
            font-weight: 400;
            margin-left: 6px;
        }

        /* ── Questions grid (2 cols using table) ─────────── */
        .questions-table { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .questions-table td { vertical-align: top; width: 50%; }

        .question-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .question-card.full-width { }

        .q-header { display: table; width: 100%; margin-bottom: 6px; }
        .q-num-label { display: table-cell; width: 30px; font-size: 10px; color: #94a3b8; font-weight: 700; vertical-align: top; padding-top: 1px; }
        .q-label    { display: table-cell; font-size: 12px; font-weight: 600; color: #1e293b; }
        .q-required { color: #ef4444; }
        .q-key { font-size: 9px; color: #94a3b8; margin-top: 1px; font-family: monospace; }

        .q-type-badge {
            float: right;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .q-field {
            margin-top: 6px;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            background: #ffffff;
            padding: 6px 8px;
            min-height: 24px;
            font-size: 10px;
            color: #cbd5e1;
            font-style: italic;
        }
        .q-field-tall { min-height: 50px; }

        .q-options { margin-top: 5px; }
        .q-option-chip {
            display: inline-block;
            background: #f1f5f9;
            border-radius: 4px;
            padding: 1px 6px;
            font-size: 9px;
            color: #64748b;
            margin: 1px 2px 1px 0;
        }

        .q-bool-options { margin-top: 5px; }
        .q-bool-opt {
            display: inline-block;
            border: 1px dashed #d1d5db;
            border-radius: 6px;
            background: #ffffff;
            padding: 3px 10px;
            font-size: 10px;
            color: #cbd5e1;
            margin-right: 6px;
        }

        .q-rating-opts { margin-top: 5px; }
        .q-rating-box {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 1px dashed #d1d5db;
            border-radius: 4px;
            background: #ffffff;
            text-align: center;
            line-height: 24px;
            font-size: 10px;
            color: #cbd5e1;
            margin-right: 4px;
        }

        /* ── Empty section ────────────────────────────────── */
        .empty-section {
            font-size: 11px;
            color: #94a3b8;
            font-style: italic;
        }

        /* ── Footer ───────────────────────────────────────── */
        .footer {
            padding: 12px 40px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .footer table { width: 100%; }
        .footer td { font-size: 10px; color: #94a3b8; }
        .footer td.right { text-align: right; }
    </style>
</head>
<body>
    @php
        $typeLabels = [
            'discovery'      => 'Discovery',
            'qualification'  => 'Qualification',
            'proposal_input' => 'Proposal Input',
            'sales_offer'    => 'Sales Offer',
            'custom'         => 'Custom',
        ];
        $langLabels = ['en' => 'English', 'pl' => 'Polish', 'pt' => 'Portuguese'];
        $sections   = $template->sections ?? [];
        $totalQs    = collect($sections)->sum(fn ($s) => count($s['questions'] ?? []));
        $typeLabel  = $typeLabels[$template->type] ?? ucfirst($template->type ?? '');
        $typeClass  = 'badge-type-' . ($template->type ?? 'custom');
    @endphp

    {{-- ── Header ──────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            <p class="doc-label">Briefing Template</p>
            <p class="doc-title">{{ $template->title }}</p>
            @if($template->description)
                <p class="doc-description">{{ $template->description }}</p>
            @endif
        </div>
        <div class="header-right">
            <div>
                <span class="badge {{ $typeClass }}">{{ $typeLabel }}</span>
            </div>
            <div style="margin-top:4px;">
                <span class="badge {{ $template->is_active ? 'badge-active' : 'badge-inactive' }}">
                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="badge badge-gray">{{ $langLabels[$template->language] ?? strtoupper($template->language) }}</span>
            </div>
            @if($template->service_slug)
                <div style="margin-top:4px;">
                    <span class="badge badge-gray">{{ $template->service_slug }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Meta row ─────────────────────────────────────────── --}}
    <div class="meta-row">
        <table>
            <tr>
                <td>
                    <span class="label">Sections</span>
                    {{ count($sections) }}
                </td>
                <td>
                    <span class="label">Questions</span>
                    {{ $totalQs }}
                </td>
                <td>
                    <span class="label">Business</span>
                    {{ $template->business?->name ?? 'Global' }}
                </td>
                <td>
                    <span class="label">Created</span>
                    {{ $template->created_at?->format('d M Y') ?? '—' }}
                </td>
                <td>
                    <span class="label">Template ID</span>
                    #{{ $template->id }}
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Sections ─────────────────────────────────────────── --}}
    @if(count($sections) > 0)
        @foreach($sections as $sIdx => $section)
            @php $questions = $section['questions'] ?? []; @endphp
            <div class="section">
                <div class="section-heading">
                    <div class="section-num">
                        <div class="section-num-box">{{ $sIdx + 1 }}</div>
                    </div>
                    <div class="section-title">
                        {{ $section['title'] }}
                        @if(!empty($section['key']))
                            <span class="section-key">{{ $section['key'] }}</span>
                        @endif
                    </div>
                </div>

                @if(count($questions) > 0)
                    <table class="questions-table">
                        @php
                            $pairs = array_chunk($questions, 2);
                        @endphp
                        @foreach($pairs as $pair)
                            <tr>
                                @foreach($pair as $qIdx => $question)
                                    @php
                                        $qType     = $question['type'] ?? 'text';
                                        $absIdx    = ($loop->parent->index * 2) + $qIdx;
                                        $qTypeLbl  = match($qType) {
                                            'text'     => 'Text',
                                            'textarea' => 'Textarea',
                                            'select'   => 'Select',
                                            'boolean'  => 'Yes/No',
                                            'rating'   => 'Rating',
                                            default    => $qType,
                                        };
                                    @endphp
                                    <td>
                                        <div class="question-card">
                                            <div class="q-type-badge">{{ $qTypeLbl }}</div>
                                            <div class="q-header">
                                                <div class="q-num-label">{{ $sIdx + 1 }}.{{ $absIdx + 1 }}</div>
                                                <div class="q-label">
                                                    {{ $question['label'] }}
                                                    @if(!empty($question['required']))
                                                        <span class="q-required">*</span>
                                                    @endif
                                                    @if(!empty($question['key']))
                                                        <div class="q-key">{{ $question['key'] }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($qType === 'textarea')
                                                <div class="q-field q-field-tall">
                                                    {{ $question['placeholder'] ?? 'Multi-line text…' }}
                                                </div>

                                            @elseif($qType === 'select')
                                                <div class="q-field">Select an option ▾</div>
                                                @if(!empty($question['options']))
                                                    <div class="q-options">
                                                        @foreach($question['options'] as $opt)
                                                            <span class="q-option-chip">{{ $opt }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                            @elseif($qType === 'boolean')
                                                <div class="q-bool-options">
                                                    <span class="q-bool-opt">Yes</span>
                                                    <span class="q-bool-opt">No</span>
                                                </div>

                                            @elseif($qType === 'rating')
                                                <div class="q-rating-opts">
                                                    @foreach([1,2,3,4,5] as $n)
                                                        <span class="q-rating-box">{{ $n }}</span>
                                                    @endforeach
                                                </div>

                                            @else
                                                <div class="q-field">
                                                    {{ $question['placeholder'] ?? 'Short text…' }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                                {{-- pad odd row --}}
                                @if(count($pair) === 1)
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                @else
                    <p class="empty-section">No questions in this section.</p>
                @endif
            </div>
        @endforeach
    @else
        <div style="padding: 40px; text-align: center; color: #94a3b8; font-style: italic;">
            No sections defined in this template.
        </div>
    @endif

    {{-- ── Footer ──────────────────────────────────────────── --}}
    <div class="footer">
        <table>
            <tr>
                <td>
                    Generated: {{ now()->format('d M Y, H:i') }}
                    @if($template->business)
                        &middot; {{ $template->business->name }}
                    @endif
                </td>
                <td class="right">{{ config('app.name') }} &middot; Briefing Template #{{ $template->id }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
