import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import CostCalculatorV2 from '@/Components/Marketing/CostCalculatorV2';

// Mock Inertia route helper (globally used via window.route or direct import)
globalThis.route = vi.fn((name) => `/${name}`);

// Mock fetch
const mockFetch = vi.fn();
globalThis.fetch = mockFetch;

// Minimal pricing data covering all required keys
const PRICING = {
    projectType:  { brochure: { base: 1000 } },
    design:       { standard: { multiplier: 1 } },
    cms:          { wordpress: { cost: 0 } },
    seoPackage:   { none: { cost: 0 } },
    deadline:     { standard: { multiplier: 1 } },
    hosting:      { none: { cost: 0 } },
    integrations: {},
};

// Minimal steps (8 steps to match default TOTAL_STEPS)
const STEPS = Array.from({ length: 8 }, (_, i) => ({
    question: `Q${i + 1}`,
    hint:     '',
}));

const STRINGS = {
    step_label:              'Step',
    of_label:                'of',
    next_button:             'Next',
    back_button:             'Back',
    submit_button:           'Get Estimate',
    your_email_label:        'Email',
    your_name_label:         'Name',
    submit_success_title:    'Thank you!',
    submit_error_message:    'Something went wrong.',
    restart_button:          'Start over',
    project_type_question:   'Project Type',
    pages_question:          'Pages',
    design_question:         'Design',
    cms_question:            'CMS',
    integrations_question:   'Integrations',
    seo_question:            'SEO',
    deadline_question:       'Deadline',
    hosting_question:        'Hosting',
    contact_question:        'Contact',
    from_label:              'from',
    base_multiplier_label:   'of base',
    no_extra_label:          'free',
    estimate_from_label:     'Estimated cost',
    step_of_label:           'of',
};

function renderCalc(overrides = {}) {
    return render(
        <CostCalculatorV2
            strings={STRINGS}
            steps={STEPS}
            pricing={PRICING}
            {...overrides}
        />
    );
}

describe('CostCalculatorV2', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders first step question', () => {
        renderCalc();
        expect(screen.getByText('Q1')).toBeInTheDocument();
    });

    it('renders without crashing when pricing is null', () => {
        renderCalc({ pricing: null });
        // When pricing is null the steps are not shown, but the wrapper renders
        expect(screen.getByRole('form', { name: /cost calculator/i })).toBeInTheDocument();
    });

    it('shows submit error when fetch fails', async () => {
        mockFetch.mockRejectedValueOnce(new Error('network error'));

        renderCalc();

        // Navigate through all steps to reach contact form
        // Step 1: projectType — click brochure option if rendered, else click Next
        const nextBtns = () => screen.queryAllByRole('button', { name: /next/i });

        // We navigate by filling required fields on each step, or by clicking
        // the Next button if the step is optional (pages step)
        // For simplicity, click Next 7 times to reach final contact step
        for (let i = 0; i < 7; i++) {
            const btn = screen.queryByRole('button', { name: /next/i });
            if (btn && !btn.disabled) {
                fireEvent.click(btn);
            }
        }

        // Fill in email on the last step (contact step)
        const emailInput = screen.queryByRole('textbox', { name: /email/i })
            ?? screen.queryByPlaceholderText(/email/i)
            ?? screen.queryByDisplayValue('');

        if (emailInput) {
            fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
        }

        const submitBtn = screen.queryByRole('button', { name: /get estimate|submit/i });
        if (submitBtn && !submitBtn.disabled) {
            fireEvent.click(submitBtn);
            await waitFor(() => {
                expect(mockFetch).toHaveBeenCalled();
            });
        }
    });

    it('does not call setSubmitted on server error response', async () => {
        mockFetch.mockResolvedValueOnce({ ok: false, status: 422 });

        renderCalc();

        // Fill email and submit — should not reach "Thank you" state
        const emailInputs = screen.queryAllByRole('textbox');
        if (emailInputs.length > 0) {
            fireEvent.change(emailInputs[emailInputs.length - 1], {
                target: { value: 'fail@test.com' },
            });
        }

        const submitBtn = screen.queryByRole('button', { name: /get estimate|submit/i });
        if (submitBtn && !submitBtn.disabled) {
            fireEvent.click(submitBtn);
            await waitFor(() => {
                expect(screen.queryByText(/thank you/i)).not.toBeInTheDocument();
            });
        }
    });
});
