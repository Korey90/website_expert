export default function ClientSubmitted({ briefingTitle }) {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
            <div className="text-center max-w-md">
                <div className="text-6xl mb-4">✅</div>
                <h1 className="text-2xl font-bold text-gray-900">Thank you!</h1>
                {briefingTitle && (
                    <p className="mt-1 text-sm text-gray-400">{briefingTitle}</p>
                )}
                <p className="mt-3 text-gray-500">
                    Your briefing has been submitted successfully. We will be in touch soon.
                </p>
            </div>
        </div>
    );
}
