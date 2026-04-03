import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * useLeads — hook do zarządzania leadem: przypisanie, zmiana etapu, won/lost.
 *
 * @param {number} leadId
 */
export function useLeads(leadId) {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState(null);

    const request = (method, routeName, params = {}, data = {}) => {
        setProcessing(true);
        setError(null);

        router[method](route(routeName, { lead: leadId, ...params }), data, {
            preserveScroll: true,
            onError: (errors) => setError(Object.values(errors)[0] ?? 'An error occurred'),
            onFinish: () => setProcessing(false),
        });
    };

    const assign = (userId) => {
        request('put', 'leads.assign', {}, { assigned_to: userId });
    };

    const changeStage = (stageId) => {
        request('put', 'leads.stage', {}, { pipeline_stage_id: stageId });
    };

    const markWon = () => {
        if (!window.confirm('Mark this lead as Won?')) return;
        request('post', 'leads.won');
    };

    const markLost = (reason) => {
        if (!reason?.trim()) return;
        request('post', 'leads.lost', {}, { reason });
    };

    return { processing, error, assign, changeStage, markWon, markLost };
}
