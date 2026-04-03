import { router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

function getErrorMessage(error, fallback) {
    return error?.response?.data?.message ?? fallback;
}

function getValidationErrors(error) {
    if (error?.response?.status === 422 && error?.response?.data?.errors) {
        return error.response.data.errors;
    }

    return {};
}

export default function useAiLandingGenerator() {
    const [variant, setVariant] = useState(null);
    const [errors, setErrors] = useState({});
    const [notice, setNotice] = useState(null);
    const [isGenerating, setIsGenerating] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [regeneratingSections, setRegeneratingSections] = useState({});

    const updateVariant = (updater) => {
        setVariant((current) => {
            if (!current) {
                return current;
            }

            return typeof updater === 'function' ? updater(current) : updater;
        });
    };

    const updateSection = (index, nextSection) => {
        updateVariant((current) => ({
            ...current,
            sections: current.sections.map((section, sectionIndex) => (
                sectionIndex === index ? nextSection : section
            )),
        }));
    };

    const generate = async (payload, fallbackError) => {
        setIsGenerating(true);
        setErrors({});
        setNotice(null);

        try {
            const response = await axios.post(route('landing-pages.ai.generate'), payload);
            setVariant(response.data.variant);
            setNotice({ type: 'success', text: response.data.message });
            return response.data.variant;
        } catch (error) {
            setErrors(getValidationErrors(error));
            setNotice({ type: 'error', text: getErrorMessage(error, fallbackError) });
            throw error;
        } finally {
            setIsGenerating(false);
        }
    };

    const regenerateSection = async (sectionType, instruction, fallbackError) => {
        if (!variant?.id) {
            return null;
        }

        setRegeneratingSections((current) => ({ ...current, [sectionType]: true }));
        setErrors({});
        setNotice(null);

        try {
            const response = await axios.post(route('landing-pages.ai.regenerate-section', variant.id), {
                section_type: sectionType,
                instruction,
            });

            setVariant(response.data.variant);
            setNotice({ type: 'success', text: response.data.message });
            return response.data.variant;
        } catch (error) {
            setNotice({ type: 'error', text: getErrorMessage(error, fallbackError) });
            throw error;
        } finally {
            setRegeneratingSections((current) => ({ ...current, [sectionType]: false }));
        }
    };

    const save = async (payload, fallbackError) => {
        if (!variant?.id) {
            return null;
        }

        setIsSaving(true);
        setErrors({});
        setNotice(null);

        try {
            const response = await axios.post(route('landing-pages.ai.save', variant.id), payload);
            setNotice({ type: 'success', text: response.data.message });

            if (response.data.redirect_url) {
                router.visit(response.data.redirect_url);
            }

            return response.data;
        } catch (error) {
            setErrors(getValidationErrors(error));
            setNotice({ type: 'error', text: getErrorMessage(error, fallbackError) });
            throw error;
        } finally {
            setIsSaving(false);
        }
    };

    return {
        variant,
        errors,
        notice,
        isGenerating,
        isSaving,
        regeneratingSections,
        setNotice,
        generate,
        regenerateSection,
        save,
        updateVariant,
        updateSection,
    };
}