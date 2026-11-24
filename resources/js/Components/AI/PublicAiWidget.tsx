import axios from 'axios';
import { useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from 'react';
import { MessageCircle, Send, RotateCcw, X, Bot, ShieldCheck, LoaderCircle } from 'lucide-react';

type Locale = 'en' | 'pl' | 'pt';
type MessageRole = 'user' | 'assistant';
type Source = { document_id: string; title: string; version: number; quote: string; score: number };
type ChatMessage = { id: string; role: MessageRole; text: string; status: string | null; sources: Source[] };
type ChatResponse = { status: string; answer: string; sources: Source[] };
type BootstrapResponse = { enabled: boolean; name: string | null };
type HistoryResponse = { messages: ChatMessage[] };

type Labels = {
    button: string; close: string; title: string; subtitle: string; welcome: string;
    placeholder: string; send: string; restart: string; loading: string; error: string;
    sources: string; privacy: string; privacyLink: string; newChat: string;
};

const copy: Record<Locale, Labels> = {
    en: {
        button: 'Ask Website Expert', close: 'Close chat', title: 'Website Expert AI',
        subtitle: 'Answers based on verified company information',
        welcome: 'Hi! What would you like to know about our services?',
        placeholder: 'Ask about our services…', send: 'Send message', restart: 'New conversation',
        loading: 'Checking our knowledge base…', error: 'The assistant is temporarily unavailable. Please try again.',
        sources: 'Knowledge sources', privacy: 'AI may make mistakes. Please avoid sharing sensitive data. See our ',
        privacyLink: 'privacy policy', newChat: 'Start a new conversation',
    },
    pl: {
        button: 'Zapytaj Website Expert', close: 'Zamknij czat', title: 'Website Expert AI',
        subtitle: 'Odpowiedzi tylko na podstawie zatwierdzonej wiedzy',
        welcome: 'Cześć! W czym mogę Ci pomóc?',
        placeholder: 'Zapytaj o nasze usługi…', send: 'Wyślij wiadomość', restart: 'Nowa rozmowa',
        loading: 'Sprawdzam naszą bazę wiedzy…', error: 'Asystent jest chwilowo niedostępny. Spróbuj ponownie.',
        sources: 'Źródła wiedzy', privacy: 'AI może się mylić. Nie podawaj wrażliwych danych. Zobacz ',
        privacyLink: 'politykę prywatności', newChat: 'Rozpocznij nową rozmowę',
    },
    pt: {
        button: 'Pergunte à Website Expert', close: 'Fechar chat', title: 'Website Expert AI',
        subtitle: 'Respostas baseadas em informações aprovadas',
        welcome: 'Olá! Como posso ajudar?',
        placeholder: 'Pergunte sobre os nossos serviços…', send: 'Enviar mensagem', restart: 'Nova conversa',
        loading: 'A consultar a base de conhecimento…', error: 'O assistente está temporariamente indisponível. Tente novamente.',
        sources: 'Fontes de conhecimento', privacy: 'A IA pode cometer erros. Evite partilhar dados sensíveis. Consulte a ',
        privacyLink: 'política de privacidade', newChat: 'Iniciar uma nova conversa',
    },
};

function detectLocale(): Locale {
    const path = window.location.pathname.split('/')[1];
    const lang = path || document.documentElement.lang.slice(0, 2);
    return lang === 'pl' || lang === 'pt' ? lang : 'en';
}

const requestConfig = {
    withXSRFToken: true,
    withCredentials: true,
    headers: { Accept: 'application/json' },
};


type LeadLabels = {
    cta: string; heading: string; name: string; email: string; company: string; project: string;
    consent: string; send: string; submitted: string; error: string; back: string;
};

const leadCopy: Record<Locale, LeadLabels> = {
    en: {
        cta: 'Request a free quote', heading: 'Tell us about your project', name: 'Your name',
        email: 'Email address', company: 'Company (optional)', project: 'What would you like to build?',
        consent: 'I agree that Website Expert may contact me about this enquiry and process these details under its privacy policy.',
        send: 'Send enquiry', submitted: 'Thanks! Your enquiry has been sent.',
        error: 'We could not send your enquiry. Try again or use the contact page.',
        back: 'Back to chat',
    },
    pl: {
        cta: 'Poproś o bezpłatną wycenę', heading: 'Opowiedz o swoim projekcie', name: 'Imię i nazwisko',
        email: 'Adres e-mail', company: 'Firma (opcjonalnie)', project: 'Co chcesz stworzyć?',
        consent: 'Zgadzam się na kontakt Website Expert w sprawie zapytania oraz przetwarzanie podanych danych zgodnie z polityką prywatności.',
        send: 'Wyślij zapytanie', submitted: 'Dziękujemy! Zapytanie zostało wysłane.',
        error: 'Nie udało się wysłać zapytania. Spróbuj ponownie lub skorzystaj z formularza kontaktowego.',
        back: 'Powrót do czatu',
    },
    pt: {
        cta: 'Pedir orçamento gratuito', heading: 'Fale-nos do seu projeto', name: 'O seu nome',
        email: 'Email', company: 'Empresa (opcional)', project: 'O que gostaria de criar?',
        consent: 'Concordo que a Website Expert me contacte sobre este pedido e trate os dados segundo a política de privacidade.',
        send: 'Enviar pedido', submitted: 'Obrigado! O seu pedido foi enviado.',
        error: 'Não foi possível enviar o pedido. Tente novamente ou use o formulário de contacto.',
        back: 'Voltar ao chat',
    },
};

function AiLeadCapture({ locale, onClose }: { locale: Locale; onClose: () => void }) {
    const labels = leadCopy[locale];
    const [form, setForm] = useState({ name: '', email: '', company: '', project: '', consent: false });
    const [sending, setSending] = useState(false);
    const [done, setDone] = useState(false);
    const [error, setError] = useState('');

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (sending || !form.consent) return;

        setSending(true);
        setError('');
        try {
            await axios.post('/ai/lead', { ...form, locale, website: '' }, requestConfig);
            setDone(true);
        } catch {
            setError(labels.error);
        } finally {
            setSending(false);
        }
    };

    return (
        <section className="max-h-[50%] overflow-y-auto border-t border-neutral-200 bg-white px-4 py-3 dark:border-neutral-700 dark:bg-neutral-900">
            <div className="flex items-center justify-between gap-2">
                <h3 className="font-semibold text-sm">{labels.heading}</h3>
                <button type="button" onClick={onClose} className="text-xs underline">{labels.back}</button>
            </div>
            {done ? (
                <p role="status" className="mt-3 text-sm text-green-700 dark:text-green-400">{labels.submitted}</p>
            ) : (
                <form onSubmit={submit} className="mt-3 space-y-2">
                    <input aria-label={labels.name} placeholder={labels.name} autoComplete="name"
                        required minLength={2} maxLength={120} value={form.name}
                        onChange={e => setForm(old => ({ ...old, name: e.target.value }))}
                        className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white" />
                    <input aria-label={labels.email} placeholder={labels.email} type="email" autoComplete="email"
                        required maxLength={254} value={form.email}
                        onChange={e => setForm(old => ({ ...old, email: e.target.value }))}
                        className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white" />
                    <input aria-label={labels.company} placeholder={labels.company} autoComplete="organization"
                        maxLength={120} value={form.company}
                        onChange={e => setForm(old => ({ ...old, company: e.target.value }))}
                        className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white" />
                    <textarea aria-label={labels.project} placeholder={labels.project} required rows={2} minLength={10}
                        maxLength={1000} value={form.project}
                        onChange={e => setForm(old => ({ ...old, project: e.target.value }))}
                        className="w-full rounded-lg border border-neutral-300 bg-white p-2 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white" />
                    <label className="flex items-start gap-2 text-xs text-neutral-600 dark:text-neutral-300">
                        <input type="checkbox" required checked={form.consent}
                            onChange={e => setForm(old => ({ ...old, consent: e.target.checked }))}
                            className="mt-0.5 accent-[#ff2b17]" />
                        <span>{labels.consent} <a href="/privacy-policy" className="underline">{copy[locale].privacyLink}</a></span>
                    </label>
                    {error && <p role="alert" className="text-xs text-red-600">{error}</p>}
                    <button type="submit" disabled={sending || !form.consent}
                        className="rounded-lg bg-[#ff2b17] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                        {labels.send}
                    </button>
                </form>
            )}
        </section>
    );
}

export default function PublicAiWidget() {
    const [enabled, setEnabled] = useState(false);
    const [leadOpen, setLeadOpen] = useState(false);
    const [name, setName] = useState('Website Expert AI');
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [input, setInput] = useState('');
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [error, setError] = useState('');
    const [locale, setLocale] = useState<Locale>('en');
    const bottomRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);
    const openerRef = useRef<HTMLButtonElement>(null);
    const panelId = 'website-expert-ai-chat-panel';
    const labels = copy[locale];

    useEffect(() => {
        setLocale(detectLocale());
        let mounted = true;

        axios.get<BootstrapResponse>('/ai/bootstrap', requestConfig)
            .then(({ data }) => {
                if (mounted && data.enabled) {
                    setEnabled(true);
                    setName(data.name || 'Website Expert AI');
                }
            })
            .catch(() => { /* Fail closed if the public assistant is disabled. */ });

        return () => { mounted = false; };
    }, []);

    useEffect(() => {
        if (!open) return;
        let mounted = true;
        axios.get<HistoryResponse>('/ai/history', requestConfig)
            .then(({ data }) => { if (mounted) setMessages(data.messages); })
            .catch(() => { if (mounted) setError(copy[locale].error); });

        inputRef.current?.focus();
        return () => { mounted = false; };
    }, [open, locale]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ block: 'end', behavior: 'smooth' });
    }, [messages, busy]);

    useEffect(() => {
        if (!open) return;
        const onEscape = (event: globalThis.KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                openerRef.current?.focus();
            }
        };
        window.addEventListener('keydown', onEscape);
        return () => window.removeEventListener('keydown', onEscape);
    }, [open]);

    const sendMessage = async (event?: FormEvent<HTMLFormElement>) => {
        event?.preventDefault();
        const text = input.trim();
        if (busy || !text || text.length > 2000) return;

        const id = crypto.randomUUID();
        setInput('');
        setError('');
        setBusy(true);
        setMessages(previous => [...previous, { id, role: 'user', text, status: null, sources: [] }]);

        try {
            const { data } = await axios.post<ChatResponse>(
                '/ai/message', { message: text, locale, client_message_id: id, website: '' }, requestConfig,
            );

            setMessages(previous => [...previous, {
                id: crypto.randomUUID(), role: 'assistant', text: data.answer,
                status: data.status, sources: data.sources ?? [],
            }]);
        } catch {
            setError(labels.error);
        } finally {
            setBusy(false);
            inputRef.current?.focus();
        }
    };

    const restart = async () => {
        if (busy) return;
        setBusy(true);
        setError('');

        try {
            await axios.post('/ai/restart', {}, requestConfig);
            setMessages([]);
        } catch {
            setError(labels.error);
        } finally {
            setBusy(false);
        }
    };

    const onComposerKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.nativeEvent.isComposing) {
            event.preventDefault();
            void sendMessage();
        }
    };

    if (!enabled) return null;

    return (
        <div className="fixed bottom-4 right-4 z-[70] flex flex-col items-end gap-3 sm:bottom-6 sm:right-6">
            {open && (
                <section
                    id={panelId}
                    role="dialog"
                    aria-modal="false"
                    aria-label={labels.title}
                    className="flex h-[min(680px,calc(100dvh-110px))] w-[min(400px,calc(100vw-32px))] flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white text-neutral-900 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900 dark:text-white"
                >
                    <header className="flex items-center gap-3 bg-neutral-950 px-4 py-4 text-white">
                        <span className="rounded-xl bg-[#ff2b17] p-2"><Bot size={20} aria-hidden="true" /></span>
                        <div className="min-w-0 flex-1">
                            <h2 className="truncate font-semibold">{name}</h2>
                            <p className="text-xs text-neutral-300">{labels.subtitle}</p>
                        </div>
                        <button
                            type="button" onClick={() => { setOpen(false); openerRef.current?.focus(); }}
                            aria-label={labels.close}
                            className="rounded-lg p-2 hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-white"
                        >
                            <X size={20} aria-hidden="true" />
                        </button>
                    </header>

                    <div className="flex-1 space-y-3 overflow-y-auto bg-neutral-50 px-4 py-4 dark:bg-neutral-950" role="log" aria-live="polite" aria-relevant="additions">
                        {messages.length === 0 && (
                            <div className="max-w-[90%] rounded-2xl rounded-tl-sm border border-neutral-200 bg-white p-3 text-sm dark:border-neutral-700 dark:bg-neutral-800">
                                {labels.welcome}
                            </div>
                        )}
                        {messages.map(message => (
                            <div
                                key={message.id}
                                className={message.role === 'user'
                                    ? 'ml-auto max-w-[94%] rounded-2xl rounded-br-sm bg-[#ff2b17] p-3 text-sm text-white whitespace-pre-wrap break-words'
                                    : 'mr-auto max-w-[94%] rounded-2xl rounded-bl-sm border border-neutral-200 bg-white p-3 text-sm whitespace-pre-wrap break-words dark:border-neutral-700 dark:bg-neutral-800'}
                            >
                                <p>{message.text}</p>
                                {message.sources.length > 0 && (
                                    <details className="mt-2 border-t border-neutral-200 pt-2 text-xs dark:border-neutral-600">
                                        <summary className="cursor-pointer font-semibold">{labels.sources}</summary>
                                        <ul className="mt-2 space-y-1">
                                            {message.sources.map(source => (
                                                <li key={source.document_id + source.version}>
                                                    {source.title} · v{source.version}
                                                </li>
                                            ))}
                                        </ul>
                                    </details>
                                )}
                            </div>
                        ))}
                        {busy && (
                            <p className="flex items-center gap-2 text-xs text-neutral-500" role="status">
                                <LoaderCircle className="animate-spin" size={16} /> {labels.loading}
                            </p>
                        )}
                        <div ref={bottomRef} />
                    </div>

                    {leadOpen ? (
                        <AiLeadCapture locale={locale} onClose={() => setLeadOpen(false)} />
                    ) : (
                        <div className="border-t border-neutral-200 px-4 py-2 dark:border-neutral-700">
                            <button type="button" onClick={() => setLeadOpen(true)}
                                className="w-full rounded-lg border border-[#ff2b17] px-3 py-2 text-sm font-semibold text-[#ff2b17] hover:bg-red-50 dark:hover:bg-red-950">
                                {leadCopy[locale].cta}
                            </button>
                        </div>
                    )}
                    <div className="border-t border-neutral-200 px-4 py-3 dark:border-neutral-700">
                        {error && <p role="alert" className="mb-2 text-sm text-red-600">{error}</p>}
                        <form onSubmit={sendMessage} className="flex items-end gap-2">
                            <label htmlFor="public-ai-question" className="sr-only">{labels.placeholder}</label>
                            <textarea
                                ref={inputRef}
                                id="public-ai-question"
                                value={input}
                                onChange={event => setInput(event.target.value)}
                                onKeyDown={onComposerKeyDown}
                                maxLength={2000}
                                rows={2}
                                disabled={busy}
                                placeholder={labels.placeholder}
                                className="max-h-28 min-h-12 flex-1 resize-none rounded-xl border border-neutral-300 bg-white p-3 text-sm text-neutral-900 focus:border-[#ff2b17] focus:ring-[#ff2b17] dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                            />
                            <button
                                type="submit" aria-label={labels.send} disabled={busy || !input.trim()}
                                className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#ff2b17] text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Send size={19} aria-hidden="true" />
                            </button>
                        </form>
                        <div className="mt-2 flex items-center justify-between gap-3">
                            <p className="text-[11px] leading-relaxed text-neutral-500">
                                <ShieldCheck size={12} className="mr-1 inline" aria-hidden="true" />
                                {labels.privacy}
                                <a href="/privacy-policy" className="underline hover:text-[#ff2b17]">{labels.privacyLink}</a>.
                            </p>
                            <button type="button" onClick={restart} disabled={busy} title={labels.restart}
                                aria-label={labels.newChat} className="shrink-0 rounded-lg p-2 text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-800">
                                <RotateCcw size={16} aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </section>
            )}
            <button
                type="button"
                ref={openerRef}
                onClick={() => setOpen(value => !value)}
                aria-label={open ? labels.close : labels.button}
                aria-expanded={open}
                aria-controls={panelId}
                className="flex items-center gap-2 rounded-full bg-[#ff2b17] px-5 py-3 font-bold text-white shadow-lg hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#ff2b17]"
            >
                {open ? <X size={20} aria-hidden="true" /> : <MessageCircle size={20} aria-hidden="true" />}
                <span className="text-sm">{open ? labels.close : labels.button}</span>
            </button>
        </div>
    );
}
