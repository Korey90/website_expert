import useRankCheckerTrans from '@/Hooks/useRankCheckerTrans';
import { pushEvent } from '@/utils/dataLayer';

export interface RankCheckResult {
    status: 'found' | 'not_found' | 'failed';
    keyword: string;
    domain: string;
    location: string;
    device: string;
    language: string;
    position: number | null;
    ranking_url: string | null;
    provider: string;
    from_cache: boolean;
    message: string | null;
}

interface GoogleRankResultProps {
    result: RankCheckResult;
    /** Where the CTA links to. Defaults to /services/seo (ignored when onCtaAction is set). */
    ctaHref?: string;
    /** When set, the CTA becomes a button calling this (with the checked domain) instead of navigating to ctaHref. */
    onCtaAction?: (domain: string) => void;
}

function interpretationKey(position: number): string {
    if (position <= 3) return 'result.interpretation.top3';
    if (position <= 10) return 'result.interpretation.top10';
    if (position <= 20) return 'result.interpretation.top20';
    if (position <= 50) return 'result.interpretation.top50';
    return 'result.interpretation.top100';
}

export default function GoogleRankResult({ result, ctaHref = '/services/seo', onCtaAction }: GoogleRankResultProps) {
    const t = useRankCheckerTrans();

    function handleCtaClick(kind: 'good' | 'bad') {
        pushEvent('rank_checker_cta_click', { kind, status: result.status, position: result.position ?? undefined });
        onCtaAction?.(result.domain);
    }

    if (result.status === 'failed') {
        return (
            <div role="alert" className="mt-6 rounded-xl border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/10 p-6 text-center">
                <p className="font-semibold text-red-700 dark:text-red-400">{t('result.error_title')}</p>
                <p className="mt-1 text-sm text-red-600 dark:text-red-400/80">{t('result.error_message')}</p>
            </div>
        );
    }

    if (result.status === 'not_found') {
        return (
            <div className="mt-6 rounded-xl border border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-white/5 p-6">
                <p className="font-semibold text-neutral-900 dark:text-white">{t('result.not_found_title')}</p>
                <p className="mt-2 text-sm text-neutral-600 dark:text-white/70">
                    {t('result.not_found_message', { domain: result.domain, keyword: result.keyword })}
                </p>
                <CtaBlock kind="bad" onCtaClick={handleCtaClick} t={t} ctaHref={ctaHref} hasCtaAction={Boolean(onCtaAction)} />
                <p className="mt-4 text-xs text-neutral-400 dark:text-white/40">{t('result.disclaimer')}</p>
            </div>
        );
    }

    // status === 'found'
    const position = result.position as number;
    const isFirstPage = position <= 10;

    return (
        <div className="mt-6 rounded-xl border border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-white/5 p-6">
            <p className="text-sm text-neutral-500 dark:text-white/60">
                {t('result.checked_for', { keyword: result.keyword, location: result.location, device: result.device })}
            </p>

            <div className="mt-3 flex items-baseline gap-3">
                <span className="text-sm font-medium text-neutral-500 dark:text-white/60">{t('result.position_label')}</span>
                <span className="text-3xl font-bold text-brand-500">#{position}</span>
            </div>

            <p className="mt-1 text-sm text-neutral-600 dark:text-white/70">{t(interpretationKey(position))}</p>

            {result.ranking_url && (
                <p className="mt-3 text-sm">
                    <span className="text-neutral-500 dark:text-white/60">{t('result.ranking_page_label')}: </span>
                    <a href={result.ranking_url} target="_blank" rel="noopener noreferrer" className="text-brand-500 hover:underline break-all">
                        {result.ranking_url}
                    </a>
                </p>
            )}

            {isFirstPage && (
                <p className="mt-3 text-sm font-medium text-green-600 dark:text-green-400">{t('result.first_page_message')}</p>
            )}

            <CtaBlock kind={isFirstPage ? 'good' : 'bad'} onCtaClick={handleCtaClick} t={t} ctaHref={ctaHref} hasCtaAction={Boolean(onCtaAction)} />

            <p className="mt-4 text-xs text-neutral-400 dark:text-white/40">{t('result.disclaimer')}</p>
        </div>
    );
}

function CtaBlock({ kind, onCtaClick, t, ctaHref, hasCtaAction }: {
    kind: 'good' | 'bad';
    onCtaClick: (kind: 'good' | 'bad') => void;
    t: (key: string, replacements?: Record<string, string | number>) => string;
    ctaHref: string;
    hasCtaAction: boolean;
}) {
    function handleClick() {
        onCtaClick(kind);
    }

    const buttonClassName = 'mt-3 inline-block rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold px-5 py-2.5 transition cursor-pointer';

    if (kind === 'good') {
        return (
            <div className="mt-5 rounded-lg bg-brand-500/5 dark:bg-brand-500/10 p-4">
                <p className="text-sm font-medium text-neutral-900 dark:text-white">{t('cta.good_title')}</p>
                {hasCtaAction ? (
                    <button type="button" onClick={handleClick} className={buttonClassName}>
                        {t('cta.good_button')}
                    </button>
                ) : (
                    <a href={ctaHref} onClick={handleClick} className={buttonClassName}>
                        {t('cta.good_button')}
                    </a>
                )}
            </div>
        );
    }

    return (
        <div className="mt-5 rounded-lg bg-brand-500/5 dark:bg-brand-500/10 p-4">
            <p className="text-sm font-medium text-neutral-900 dark:text-white">{t('cta.bad_title')}</p>
            <p className="mt-1 text-sm text-neutral-600 dark:text-white/70">{t('cta.bad_body')}</p>
            {hasCtaAction ? (
                <button type="button" onClick={handleClick} className={buttonClassName}>
                    {t('cta.bad_button')}
                </button>
            ) : (
                <a href={ctaHref} onClick={handleClick} className={buttonClassName}>
                    {t('cta.bad_button')}
                </a>
            )}
        </div>
    );
}
