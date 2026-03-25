import PortalLayout from '@/Layouts/PortalLayout';
import { Link, router, usePage } from '@inertiajs/react';
import { useRef, useState, useEffect, useCallback } from 'react';

const statusConfig = {
    sent:      { color: 'bg-blue-100 text-blue-800',     label: 'Awaiting Your Signature' },
    signed:    { color: 'bg-green-100 text-green-800',   label: 'Signed' },
    expired:   { color: 'bg-orange-100 text-orange-800', label: 'Expired' },
    cancelled: { color: 'bg-red-100 text-red-800',       label: 'Cancelled' },
};

function fmt(amount, currency) {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency ?? 'GBP' }).format(amount ?? 0);
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

/* ── Signature Pad ────────────────────────────────────────────── */
function SignaturePad({ onChange }) {
    const canvasRef = useRef(null);
    const drawing = useRef(false);
    const lastPos = useRef(null);
    const [isEmpty, setIsEmpty] = useState(true);

    function getPos(e, canvas) {
        const rect = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    function startDraw(e) {
        e.preventDefault();
        drawing.current = true;
        lastPos.current = getPos(e, canvasRef.current);
    }

    function draw(e) {
        e.preventDefault();
        if (!drawing.current) return;
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        const pos = getPos(e, canvas);
        ctx.beginPath();
        ctx.moveTo(lastPos.current.x, lastPos.current.y);
        ctx.lineTo(pos.x, pos.y);
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.stroke();
        lastPos.current = pos;
        setIsEmpty(false);
        // Export with white background so signature is visible on dark themes
        const exportCanvas = document.createElement('canvas');
        exportCanvas.width = canvas.width;
        exportCanvas.height = canvas.height;
        const expCtx = exportCanvas.getContext('2d');
        expCtx.fillStyle = '#ffffff';
        expCtx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
        expCtx.drawImage(canvas, 0, 0);
        onChange(exportCanvas.toDataURL('image/png'));
    }

    function stopDraw() {
        drawing.current = false;
    }

    function clear() {
        const canvas = canvasRef.current;
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        setIsEmpty(true);
        onChange(null);
    }

    useEffect(() => {
        const canvas = canvasRef.current;
        // Scale for retina
        const dpr = window.devicePixelRatio || 1;
        canvas.width = canvas.offsetWidth * dpr;
        canvas.height = canvas.offsetHeight * dpr;
        canvas.getContext('2d').scale(dpr, dpr);
    }, []);

    return (
        <div>
            <div className="relative border-2 border-dashed border-gray-300 rounded-lg bg-white" style={{ height: 160 }}>
                <canvas
                    ref={canvasRef}
                    className="absolute inset-0 w-full h-full rounded-lg cursor-crosshair touch-none"
                    onMouseDown={startDraw}
                    onMouseMove={draw}
                    onMouseUp={stopDraw}
                    onMouseLeave={stopDraw}
                    onTouchStart={startDraw}
                    onTouchMove={draw}
                    onTouchEnd={stopDraw}
                />
                {isEmpty && (
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <span className="text-sm text-gray-400 select-none">Draw your signature here</span>
                    </div>
                )}
            </div>
            <button
                type="button"
                onClick={clear}
                className="mt-2 text-xs text-gray-500 hover:text-red-600 underline"
            >
                Clear signature
            </button>
        </div>
    );
}

/* ── Main Page ───────────────────────────────────────────────── */
export default function Contract({ client, contract }) {
    const { props } = usePage();
    const flash = props.flash ?? {};
    const cfg = statusConfig[contract.status] ?? { color: 'bg-gray-100 text-gray-700', label: contract.status };
    const isSent = contract.status === 'sent';

    // Form state
    const [signerName, setSignerName] = useState('');
    const [confirmed, setConfirmed] = useState(false);
    const [signMethod, setSignMethod] = useState('pad'); // 'pad' | 'checkbox'
    const [signatureData, setSignatureData] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState({});

    function submit(e) {
        e.preventDefault();
        const errs = {};
        if (!signerName.trim()) errs.signerName = 'Full name is required.';
        if (!confirmed) errs.confirmed = 'You must confirm you agree to the terms.';
        if (signMethod === 'pad' && !signatureData) errs.signatureData = 'Please draw your signature or switch to checkbox acceptance.';
        if (Object.keys(errs).length) { setErrors(errs); return; }

        setSubmitting(true);
        router.post(route('portal.contracts.sign', contract.id), {
            signer_name:    signerName,
            confirmed:      confirmed ? '1' : '',
            signature_data: signMethod === 'pad' ? signatureData : null,
        }, {
            onError: (e) => { setErrors(e); setSubmitting(false); },
        });
    }

    return (
        <PortalLayout client={client}>
            <div className="max-w-4xl mx-auto space-y-6">

                {/* Flash */}
                {flash.success && (
                    <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                        {flash.error}
                    </div>
                )}

                {/* Header */}
                <div className="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <Link href={route('portal.contracts')} className="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">
                            ← Back to Contracts
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">{contract.title}</h1>
                        <div className="flex items-center gap-3 mt-1">
                            <span className="text-sm text-gray-500">{contract.number}</span>
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${cfg.color}`}>
                                {cfg.label}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Summary */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-6">
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Contract Value</p>
                            <p className="mt-1 text-xl font-bold text-gray-900">{fmt(contract.value, contract.currency)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Start Date</p>
                            <p className="mt-1 text-sm font-medium text-gray-900">{fmtDate(contract.starts_at)}</p>
                        </div>
                        <div>
                            <p className="text-xs text-gray-500 uppercase tracking-wide">Expiry</p>
                            <p className="mt-1 text-sm font-medium text-gray-900">{fmtDate(contract.expires_at)}</p>
                        </div>
                        {contract.signed_at && (
                            <div>
                                <p className="text-xs text-gray-500 uppercase tracking-wide">Signed On</p>
                                <p className="mt-1 text-sm font-medium text-green-700">{fmtDate(contract.signed_at)}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Terms */}
                {contract.terms && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                            <span>📄</span>
                            <h2 className="font-semibold text-gray-900">Contract Terms</h2>
                        </div>
                        <div
                            className="px-6 py-5 prose prose-sm max-w-none text-gray-700 max-h-[500px] overflow-y-auto"
                            dangerouslySetInnerHTML={{ __html: contract.terms }}
                        />
                    </div>
                )}

                {/* Notes */}
                {contract.notes && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="font-semibold text-gray-900 mb-2">Notes</h2>
                        <p className="text-sm text-gray-700 whitespace-pre-line">{contract.notes}</p>
                    </div>
                )}

                {/* Signed info (already signed) */}
                {contract.status === 'signed' && (
                    <div className="bg-green-50 border border-green-200 rounded-xl p-6 flex items-start gap-4">
                        <div className="text-2xl">✅</div>
                        <div>
                            <p className="font-semibold text-green-800">Contract signed</p>
                            <p className="text-sm text-green-700 mt-1">
                                Signed by <strong>{contract.signer_name}</strong> on {fmtDate(contract.signed_at)}.
                            </p>
                            {contract.signer_ip && (
                                <p className="text-xs text-green-600 mt-0.5">IP: {contract.signer_ip}</p>
                            )}
                        </div>
                    </div>
                )}

                {/* Signing form (only when sent) */}
                {isSent && (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-100 bg-blue-50">
                            <h2 className="font-semibold text-gray-900 flex items-center gap-2">
                                ✍️ Sign this Contract
                            </h2>
                            <p className="text-sm text-gray-600 mt-1">
                                Please read the terms above carefully before signing.
                            </p>
                        </div>
                        <form onSubmit={submit} className="px-6 py-6 space-y-6">

                            {/* Full name */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={signerName}
                                    onChange={e => setSignerName(e.target.value)}
                                    placeholder="Enter your full legal name"
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                {errors.signerName && <p className="mt-1 text-xs text-red-600">{errors.signerName}</p>}
                            </div>

                            {/* Signature method toggle */}
                            <div>
                                <p className="text-sm font-medium text-gray-700 mb-2">Signature Method</p>
                                <div className="flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setSignMethod('pad')}
                                        className={`flex-1 py-2.5 px-4 rounded-lg border text-sm font-medium transition-colors ${
                                            signMethod === 'pad'
                                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'
                                        }`}
                                    >
                                        ✏️ Draw Signature
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setSignMethod('checkbox')}
                                        className={`flex-1 py-2.5 px-4 rounded-lg border text-sm font-medium transition-colors ${
                                            signMethod === 'checkbox'
                                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'
                                        }`}
                                    >
                                        ☑️ Electronic Acceptance
                                    </button>
                                </div>
                            </div>

                            {/* Pad or checkbox */}
                            {signMethod === 'pad' ? (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Your Signature <span className="text-red-500">*</span>
                                    </label>
                                    <SignaturePad onChange={setSignatureData} />
                                    {errors.signatureData && (
                                        <p className="mt-1 text-xs text-red-600">{errors.signatureData}</p>
                                    )}
                                </div>
                            ) : (
                                <div className="rounded-lg bg-gray-50 border border-gray-200 p-4">
                                    <p className="text-sm text-gray-600">
                                        By checking the box below, you agree that this constitutes your legally binding electronic signature.
                                    </p>
                                </div>
                            )}

                            {/* Agreement checkbox */}
                            <div>
                                <label className="flex items-start gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={confirmed}
                                        onChange={e => setConfirmed(e.target.checked)}
                                        className="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span className="text-sm text-gray-700">
                                        I have read and agree to all the terms and conditions of this contract. I understand that
                                        {signMethod === 'pad'
                                            ? ' my drawn signature above'
                                            : ' checking this box'
                                        } constitutes a legally binding electronic signature.
                                    </span>
                                </label>
                                {errors.confirmed && <p className="mt-1 text-xs text-red-600">{errors.confirmed}</p>}
                            </div>

                            <div className="flex justify-end pt-2">
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold bg-green-600 text-white hover:bg-green-700 disabled:opacity-60 disabled:cursor-not-allowed transition-colors"
                                >
                                    {submitting ? 'Signing…' : '✓ Sign & Accept Contract'}
                                </button>
                            </div>
                        </form>
                    </div>
                )}

            </div>
        </PortalLayout>
    );
}
