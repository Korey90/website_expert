import { useState, useRef, useCallback } from 'react';
import { router } from '@inertiajs/react';

function ProgressBar({ pct }) {
    return (
        <div className="w-full bg-gray-200 rounded-full h-2">
            <div
                className="bg-blue-600 h-2 rounded-full transition-all duration-500"
                style={{ width: `${pct}%` }}
            />
        </div>
    );
}

function QuestionField({ section, question, value, onChange }) {
    const inputClass =
        'mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm';

    const sectionKey  = section.key;
    const questionKey = question.key;

    const handleChange = (val) => onChange(sectionKey, questionKey, val);

    switch (question.type) {
        case 'textarea':
            return (
                <textarea
                    rows={3}
                    placeholder={question.placeholder ?? ''}
                    value={value ?? ''}
                    onChange={(e) => handleChange(e.target.value)}
                    className={inputClass}
                />
            );
        case 'select':
            return (
                <select
                    value={value ?? ''}
                    onChange={(e) => handleChange(e.target.value)}
                    className={inputClass}
                >
                    <option value="">— Select —</option>
                    {(question.options ?? []).map((opt) => (
                        <option key={opt} value={opt}>{opt}</option>
                    ))}
                </select>
            );
        case 'boolean':
            return (
                <div className="mt-2 flex gap-4">
                    {['yes', 'no'].map((opt) => (
                        <label key={opt} className="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input
                                type="radio"
                                value={opt}
                                checked={value === opt}
                                onChange={() => handleChange(opt)}
                                className="text-blue-600"
                            />
                            {opt.charAt(0).toUpperCase() + opt.slice(1)}
                        </label>
                    ))}
                </div>
            );
        case 'rating':
            return (
                <div className="mt-2 flex gap-3">
                    {[1, 2, 3, 4, 5].map((n) => (
                        <label key={n} className="flex flex-col items-center gap-0.5 text-xs cursor-pointer">
                            <input
                                type="radio"
                                value={n}
                                checked={Number(value) === n}
                                onChange={() => handleChange(n)}
                                className="text-blue-600"
                            />
                            {n}
                        </label>
                    ))}
                </div>
            );
        default:
            return (
                <input
                    type="text"
                    placeholder={question.placeholder ?? ''}
                    value={value ?? ''}
                    onChange={(e) => handleChange(e.target.value)}
                    className={inputClass}
                />
            );
    }
}

export default function ClientFill({ token, briefing, sections, answers: initialAnswers, business }) {
    const [answers, setAnswers] = useState(initialAnswers ?? {});
    const [saving, setSaving] = useState(false);
    const [lastSaved, setLastSaved] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const autosaveTimer = useRef(null);

    const totalRequired = sections.reduce(
        (sum, s) => sum + (s.questions ?? []).filter((q) => q.required).length, 0
    );
    const totalAnswered = sections.reduce((sum, s) => {
        const sk = s.key;
        return sum + (s.questions ?? []).filter((q) => q.required && answers[sk]?.[q.key]).length;
    }, 0);
    const progress = totalRequired > 0 ? Math.round((totalAnswered / totalRequired) * 100) : 0;

    const doAutosave = useCallback(async (currentAnswers) => {
        setSaving(true);
        try {
            await fetch(route('client.briefings.autosave', token), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ answers: currentAnswers }),
            });
            setLastSaved(new Date());
        } catch (_) {
            // silent — best-effort autosave
        } finally {
            setSaving(false);
        }
    }, [token]);

    const handleChange = (sectionKey, questionKey, value) => {
        setAnswers((prev) => {
            const updated = {
                ...prev,
                [sectionKey]: {
                    ...(prev[sectionKey] ?? {}),
                    [questionKey]: value,
                },
            };

            // Debounce autosave
            if (autosaveTimer.current) clearTimeout(autosaveTimer.current);
            autosaveTimer.current = setTimeout(() => doAutosave(updated), 1500);

            return updated;
        });
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (submitting) return;
        setSubmitting(true);

        router.post(
            route('client.briefings.submit', token),
            { answers },
            {
                onSuccess: () => setSubmitted(true),
                onFinish: () => setSubmitting(false),
            }
        );
    };

    if (submitted) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
                <div className="text-center max-w-md">
                    <div className="text-6xl mb-4">✅</div>
                    <h1 className="text-2xl font-bold text-gray-900">Thank you!</h1>
                    <p className="mt-2 text-gray-500">Your briefing has been submitted successfully. We will be in touch soon.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <header className="bg-white border-b border-gray-200 shadow-sm">
                <div className="max-w-2xl mx-auto px-4 py-5 flex items-center justify-between">
                    <div>
                        <p className="text-xs text-gray-400 uppercase tracking-wide">{business?.name}</p>
                        <h1 className="text-lg font-bold text-gray-900 mt-0.5">{briefing.title}</h1>
                    </div>
                    <span className="text-sm text-gray-500">{progress}% complete</span>
                </div>
                <div className="max-w-2xl mx-auto px-4 pb-3">
                    <ProgressBar pct={progress} />
                </div>
            </header>

            {/* Form */}
            <form onSubmit={handleSubmit} className="max-w-2xl mx-auto px-4 py-8 space-y-8">
                {sections.map((section) => (
                    <div key={section.key} className="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                        <h2 className="text-base font-semibold text-gray-900 mb-4">{section.title}</h2>
                        <div className="space-y-5">
                            {(section.questions ?? []).map((question) => (
                                <div key={question.key}>
                                    <label className="block text-sm font-medium text-gray-700">
                                        {question.label}
                                        {question.required && (
                                            <span className="text-red-500 ml-0.5">*</span>
                                        )}
                                    </label>
                                    <QuestionField
                                        section={section}
                                        question={question}
                                        value={answers[section.key]?.[question.key]}
                                        onChange={handleChange}
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                ))}

                {/* Submit */}
                <div className="flex items-center justify-between bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <span className="text-sm text-gray-400">
                        {saving
                            ? 'Saving…'
                            : lastSaved
                            ? `Saved at ${lastSaved.toLocaleTimeString()}`
                            : 'Not saved yet'}
                    </span>
                    <button
                        type="submit"
                        disabled={submitting}
                        className="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed transition"
                    >
                        {submitting ? 'Submitting…' : 'Submit briefing'}
                    </button>
                </div>
            </form>
        </div>
    );
}
