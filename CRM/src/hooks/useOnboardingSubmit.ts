import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiRequest } from '../services/api';
import { useOnboarding } from '../context/OnboardingContext';
import { EMAIL_RE, URL_RE } from '../constants/onboarding';
import { clearDraft } from './useDraftPersistence';
import type { Business } from '../types/api';
import type { OnboardingFormData } from '../types/onboarding';

function validate(
  formData: OnboardingFormData,
  hasTypeSelection: boolean,
): Record<string, string> {
  const errs: Record<string, string> = {};

  if (!formData.companyName.trim()) {
    errs.companyName = 'Emri i kompanisë është i detyrueshëm.';
  } else if (formData.companyName.trim().length < 2) {
    errs.companyName = 'Emri duhet të ketë të paktën 2 karaktere.';
  }

  if (!hasTypeSelection) {
    errs.businessTypeId = 'Ju lutem zgjidhni llojin e biznesit.';
  }

  if (formData.email.trim() && !EMAIL_RE.test(formData.email.trim())) {
    errs.email = 'Ju lutem vendosni një email të vlefshëm.';
  }

  if (formData.website.trim() && !URL_RE.test(formData.website.trim())) {
    errs.website = 'URL duhet të fillojë me http:// ose https://';
  }

  return errs;
}

export function validateSingleField(
  field: string,
  formData: OnboardingFormData,
  hasTypeSelection: boolean,
): string | null {
  switch (field) {
    case 'companyName':
      if (!formData.companyName.trim()) return 'Emri i kompanisë është i detyrueshëm.';
      if (formData.companyName.trim().length < 2) return 'Emri duhet të ketë të paktën 2 karaktere.';
      return null;
    case 'businessTypeId':
      return hasTypeSelection ? null : 'Ju lutem zgjidhni llojin e biznesit.';
    case 'email':
      if (formData.email.trim() && !EMAIL_RE.test(formData.email.trim()))
        return 'Ju lutem vendosni një email të vlefshëm.';
      return null;
    case 'website':
      if (formData.website.trim() && !URL_RE.test(formData.website.trim()))
        return 'URL duhet të fillojë me http:// ose https://';
      return null;
    default:
      return null;
  }
}

export function useOnboardingSubmit() {
  const navigate = useNavigate();
  const { state, dispatch, hasTypeSelection } = useOnboarding();
  const { formData } = state;

  const handleSubmit = useCallback(async () => {
    const errs = validate(formData, hasTypeSelection);
    dispatch({ type: 'SET_FIELD_ERRORS', errors: errs });
    if (Object.keys(errs).length > 0) return;

    dispatch({ type: 'SET_SUBMITTING', value: true });
    dispatch({ type: 'SET_SUBMIT_ERROR', error: null });

    try {
      const business = await apiRequest<Business>('/api/businesses', {
        method: 'POST',
        body: JSON.stringify({
          name: formData.companyName.trim(),
          description: formData.description.trim() || null,
          phone: formData.phone.trim() || null,
          email: formData.email.trim() || null,
          address: formData.address.trim() || null,
          website: formData.website.trim() || null,
          timezone: formData.timezone.trim() || null,
        }),
      });

      if (formData.businessTypeId) {
        await apiRequest<Business>(`/api/businesses/${business.id}/business-type`, {
          method: 'PUT',
          body: JSON.stringify({ business_type_id: formData.businessTypeId }),
        });
      }

      clearDraft();
      navigate('/app', { replace: true });
    } catch (err) {
      dispatch({
        type: 'SET_SUBMIT_ERROR',
        error: err instanceof Error ? err.message : 'Ndodhi një gabim gjatë ruajtjes. Provo përsëri.',
      });
    } finally {
      dispatch({ type: 'SET_SUBMITTING', value: false });
    }
  }, [formData, hasTypeSelection, dispatch, navigate]);

  const handleSkip = useCallback(() => {
    navigate('/app', { replace: true });
  }, [navigate]);

  return { handleSubmit, handleSkip };
}
