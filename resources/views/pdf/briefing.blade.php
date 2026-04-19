<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Briefing: {{ $briefing->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }

        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { font-size: 16pt; color: #1e3a8a; }
        .header .meta { margin-top: 8px; font-size: 9pt; color: #6b7280; }
        .header .meta span { margin-right: 20px; }

        .meta-grid { display: table; width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .meta-grid .cell { display: table-cell; width: 25%; padding: 6px 8px; background: #f3f4f6; border: 1px solid #e5e7eb; vertical-align: top; }
        .meta-grid .cell .label { font-size: 7pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
        .meta-grid .cell .value { font-size: 9pt; color: #1f2937; margin-top: 2px; }

        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section-title { font-size: 11pt; font-weight: bold; color: #1e3a8a; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin-bottom: 10px; }

        .questions-grid { display: table; width: 100%; border-collapse: collapse; }
        .question-row { display: table-row; }
        .question-label { display: table-cell; width: 35%; padding: 5px 8px; vertical-align: top; background: #f9fafb; border: 1px solid #e5e7eb; font-size: 8.5pt; font-weight: bold; color: #374151; }
        .question-answer { display: table-cell; padding: 5px 8px; vertical-align: top; border: 1px solid #e5e7eb; font-size: 8.5pt; color: #1f2937; }
        .question-answer.empty { color: #9ca3af; font-style: italic; }

        .notes-box { background: #fef9c3; border: 1px solid #fde047; padding: 10px 12px; margin-top: 20px; border-radius: 4px; }
        .notes-box .notes-title { font-size: 9pt; font-weight: bold; color: #854d0e; margin-bottom: 4px; }
        .notes-box p { font-size: 8.5pt; color: #713f12; }

        .footer { border-top: 1px solid #e5e7eb; margin-top: 30px; padding-top: 8px; font-size: 7.5pt; color: #9ca3af; text-align: center; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 9999px; font-size: 7pt; font-weight: bold; }
        .badge-draft       { background: #f3f4f6; color: #374151; }
        .badge-in_progress { background: #fef3c7; color: #92400e; }
        .badge-completed   { background: #d1fae5; color: #065f46; }
        .badge-cancelled   { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Briefing: {{ $briefing->title }}</h1>
        <div class="meta">
            <span>Lead: {{ $briefing->lead->title ?? '—' }}</span>
            <span>Conducted by: {{ $briefing->conductedBy->name ?? '—' }}</span>
            <span>Date: {{ ($briefing->completed_at ?? $briefing->created_at)?->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="meta-grid">
        <div class="cell">
            <div class="label">Status</div>
            <div class="value">
                <span class="badge badge-{{ $briefing->status }}">{{ ucfirst(str_replace('_', ' ', $briefing->status)) }}</span>
            </div>
        </div>
        <div class="cell">
            <div class="label">Type</div>
            <div class="value">{{ ucfirst(str_replace('_', ' ', $briefing->type)) }}</div>
        </div>
        <div class="cell">
            <div class="label">Language</div>
            <div class="value">{{ strtoupper($briefing->language) }}</div>
        </div>
        <div class="cell">
            <div class="label">Progress</div>
            <div class="value">{{ $briefing->getProgressPercentage() }}%</div>
        </div>
    </div>

    @if ($briefing->template)
        @foreach ($briefing->template->sections ?? [] as $section)
            @php
                $sectionKey = $section['key'];
                $answers    = $briefing->answers ?? [];
            @endphp
            <div class="section">
                <div class="section-title">{{ $section['title'] }}</div>
                <div class="questions-grid">
                    @foreach ($section['questions'] ?? [] as $question)
                        @php
                            $answer = $answers[$sectionKey][$question['key']] ?? null;
                        @endphp
                        <div class="question-row">
                            <div class="question-label">{{ $question['label'] }}</div>
                            <div class="question-answer @if(!$answer) empty @endif">
                                {{ $answer ?? 'Not answered' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <p style="color:#6b7280;font-style:italic;">No template attached — raw answers only.</p>
        @if ($briefing->answers)
            <pre style="font-size:8pt;background:#f9fafb;padding:10px;border:1px solid #e5e7eb;">{{ json_encode($briefing->answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    @endif

    @if ($briefing->notes)
        <div class="notes-box">
            <div class="notes-title">Internal Notes</div>
            <p>{{ $briefing->notes }}</p>
        </div>
    @endif

    <div class="footer">
        Generated by Website Expert &bull; {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
