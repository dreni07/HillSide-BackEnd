import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../services/api';
import { useOnboarding } from '../context/OnboardingContext';
import {
  ADDRESS_MIN_LEN,
  DESCRIPTION_MIN_LEN,
  EMAIL_RE,
  PHONE_MAX_DIGITS,
  PHONE_MIN_DIGITS,
  URL_RE,
} from '../constants/onboarding';
import { clearDraft } from './useDraftPersistence';
import type { Business } from '../types/api';
import type { OnboardingFormData } from '../types/onboarding';

function phoneValidationError(phone: string): string | null {
  const trimmed = phone.trim();
  if (!trimmed) return 'Telefoni është i detyrueshëm.';
  const digits = trimmed.replace(/\D/g, '');
  if (digits.length < PHONE_MIN_DIGITS) {
    return `Vendosni një numër telefoni të vlefshëm (të paktën ${PHONE_MIN_DIGITS} shifra).`;
  }
  if (digits.length > PHONE_MAX_DIGITS) return 'Numri i telefonit është shumë i gjatë.';
  return null;
}

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

  const desc = formData.description.trim();
  if (!desc) {
    errs.description = 'Përshkrimi i kompanisë është i detyrueshëm.';
  } else if (desc.length < DESCRIPTION_MIN_LEN) {
    errs.description = `Përshkrimi duhet të ketë të paktën ${DESCRIPTION_MIN_LEN} karaktere.`;
  }

  if (!hasTypeSelection) {
    errs.businessTypeId = 'Ju lutem zgjidhni llojin e biznesit.';
  }

  const phoneErr = phoneValidationError(formData.phone);
  if (phoneErr) errs.phone = phoneErr;

  const email = formData.email.trim();
  if (!email) {
    errs.email = 'Email i biznesit është i detyrueshëm.';
  } else if (!EMAIL_RE.test(email)) {
    errs.email = 'Ju lutem vendosni një email të vlefshëm.';
  }

  const address = formData.address.trim();
  if (!address) {
    errs.address = 'Adresa është e detyrueshme.';
  } else if (address.length < ADDRESS_MIN_LEN) {
    errs.address = `Adresa duhet të ketë të paktën ${ADDRESS_MIN_LEN} karaktere.`;
  }

  const website = formData.website.trim();
  if (!website) {
    errs.website = 'Website është i detyrueshëm.';
  } else if (!URL_RE.test(website)) {
    errs.website = 'URL duhet të fillojë me http:// ose https://';
  }

  if (!formData.timezone.trim()) {
    errs.timezone = 'Ju lutem zgjidhni zonën kohore.';
  }

  return errs;
}

/** `true` kur forma plotëson të gjitha kushtet e `validate` (p.sh. për të aktivizuar butonin e dërgimit). */
export function isOnboardingFormReady(
  formData: OnboardingFormData,
  hasTypeSelection: boolean,
): boolean {
  return Object.keys(validate(formData, hasTypeSelection)).length === 0;
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
    case 'description': {
      const d = formData.description.trim();
      if (!d) return 'Përshkrimi i kompanisë është i detyrueshëm.';
      if (d.length < DESCRIPTION_MIN_LEN) {
        return `Përshkrimi duhet të ketë të paktën ${DESCRIPTION_MIN_LEN} karaktere.`;
      }
      return null;
    }
    case 'businessTypeId':
      return hasTypeSelection ? null : 'Ju lutem zgjidhni llojin e biznesit.';
    case 'phone':
      return phoneValidationError(formData.phone);
    case 'email': {
      const e = formData.email.trim();
      if (!e) return 'Email i biznesit është i detyrueshëm.';
      if (!EMAIL_RE.test(e)) return 'Ju lutem vendosni një email të vlefshëm.';
      return null;
    }
    case 'address': {
      const a = formData.address.trim();
      if (!a) return 'Adresa është e detyrueshme.';
      if (a.length < ADDRESS_MIN_LEN) {
        return `Adresa duhet të ketë të paktën ${ADDRESS_MIN_LEN} karaktere.`;
      }
      return null;
    }
    case 'website': {
      const w = formData.website.trim();
      if (!w) return 'Website është i detyrueshëm.';
      if (!URL_RE.test(w)) return 'URL duhet të fillojë me http:// ose https://';
      return null;
    }
    case 'timezone':
      return formData.timezone.trim() ? null : 'Ju lutem zgjidhni zonën kohore.';
    default:
      return null;
  }
}

export function useOnboardingSubmit() {
  const navigate = useNavigate();
  const { updateUser } = useAuth();
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
          description: formData.description.trim(),
          phone: formData.phone.trim(),
          email: formData.email.trim(),
          address: formData.address.trim(),
          website: formData.website.trim(),
          timezone: formData.timezone.trim(),
        }),
      });

      if (formData.businessTypeId) {
        await apiRequest<Business>(`/api/businesses/${business.id}/business-type`, {
          method: 'PUT',
          body: JSON.stringify({ business_type_id: formData.businessTypeId }),
        });
      }

      updateUser({ onboarding_completed: true });
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
  }, [formData, hasTypeSelection, dispatch, navigate, updateUser]);

  return { handleSubmit };
}
