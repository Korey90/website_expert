import { useState, useRef, useCallback } from 'react';
import axios from 'axios';

/**
 * LogoUploader — drag-and-drop + click upload with live preview.
 * Uploads via POST /business/logo (multipart/form-data).
 * On delete sends DELETE /business/logo.
 *
 * Props:
 *  currentLogoUrl  — existing logo URL from server (nullable)
 *  uploadRoute     — Ziggy route name (default: 'business.logo.upload')
 *  deleteRoute     — Ziggy route name (default: 'business.logo.delete')
 *  onUploaded(url) — callback with new URL after successful upload
 *  onDeleted()     — callback after successful delete
 *  label           — optional label string
 */
export default function LogoUploader({
    currentLogoUrl = null,
    uploadRoute = 'business.logo.upload',
    deleteRoute  = 'business.logo.delete',
    onUploaded,
    onDeleted,
    label,
}) {
    const [preview, setPreview]   = useState(currentLogoUrl);
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError]       = useState(null);
    const inputRef = useRef(null);

    const handleFile = useCallback(async (file) => {
        if (!file) return;
        const allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowed.includes(file.type)) {
            setError('Only JPG, PNG or WebP files are allowed.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            setError('File must be smaller than 2 MB.');
            return;
        }

        setError(null);
        // Optimistic local preview
        const objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl);

        const formData = new FormData();
        formData.append('logo', file);

        try {
            setUploading(true);
            const res = await axios.post(route(uploadRoute), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            // Revoke optimistic URL, use server URL
            URL.revokeObjectURL(objectUrl);
            setPreview(res.data.logo_url);
            onUploaded?.(res.data.logo_url);
        } catch (err) {
            URL.revokeObjectURL(objectUrl);
            setPreview(currentLogoUrl);
            setError(err.response?.data?.message ?? 'Upload failed. Please try again.');
        } finally {
            setUploading(false);
        }
    }, [currentLogoUrl, uploadRoute, onUploaded]);

    const handleDelete = async () => {
        if (!confirm('Delete logo?')) return;
        try {
            await axios.delete(route(deleteRoute));
            setPreview(null);
            onDeleted?.();
        } catch {
            setError('Could not delete logo.');
        }
    };

    // Drag events
    const onDragOver = (e) => { e.preventDefault(); setDragging(true); };
    const onDragLeave = () => setDragging(false);
    const onDrop = (e) => {
        e.preventDefault();
        setDragging(false);
        handleFile(e.dataTransfer.files[0]);
    };

    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {label}
                </label>
            )}

            {preview ? (
                /* Preview + replace/delete actions */
                <div className="flex items-center gap-4">
                    <div className="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
                        <img src={preview} alt="Logo" className="h-full w-full object-contain p-1" />
                    </div>
                    <div className="flex flex-col gap-2">
                        <button
                            type="button"
                            onClick={() => inputRef.current?.click()}
                            disabled={uploading}
                            className="text-sm font-medium text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 disabled:opacity-50"
                        >
                            {uploading ? 'Uploading…' : 'Replace'}
                        </button>
                        <button
                            type="button"
                            onClick={handleDelete}
                            className="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                        >
                            Remove
                        </button>
                    </div>
                </div>
            ) : (
                /* Drop zone */
                <div
                    onDragOver={onDragOver}
                    onDragLeave={onDragLeave}
                    onDrop={onDrop}
                    onClick={() => inputRef.current?.click()}
                    className={
                        'flex flex-col items-center justify-center rounded-xl border-2 border-dashed cursor-pointer ' +
                        'py-8 px-4 text-center transition-colors ' +
                        (dragging
                            ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/10'
                            : 'border-gray-300 dark:border-gray-600 hover:border-brand-400 dark:hover:border-brand-500 bg-white dark:bg-gray-800')
                    }
                >
                    <svg className="h-8 w-8 text-gray-400 dark:text-gray-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        <span className="font-medium text-brand-600 dark:text-brand-400">Click to upload</span> or drag &amp; drop
                    </p>
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-500">PNG, JPG, WebP · max 2 MB</p>

                    {uploading && (
                        <div className="mt-3 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg className="h-4 w-4 animate-spin text-brand-500" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            Uploading…
                        </div>
                    )}
                </div>
            )}

            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="sr-only"
                onChange={(e) => handleFile(e.target.files[0])}
                tabIndex={-1}
            />

            {error && (
                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{error}</p>
            )}
        </div>
    );
}
