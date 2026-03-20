import { useEffect, useRef } from 'react';
import { DRAFT_KEY, EMPTY_FORM } from '../constants/onboarding';
import type { OnboardingFormData } from '../types/onboarding';

export function readDraft(): OnboardingFormData | null {
  try {
    const raw = sessionStorage.getItem(DRAFT_KEY);
    if (!raw) return null;
    const p = JSON.parse(raw) as Partial<OnboardingFormData>;
    return {
      companyName: typeof p.companyName === 'string' ? p.companyName : '',
      description: typeof p.description === 'string' ? p.description : '',
      phone: typeof p.phone === 'string' ? p.phone : '',
      email: typeof p.email === 'string' ? p.email : '',
      address: typeof p.address === 'string' ? p.address : '',
      website: typeof p.website === 'string' ? p.website : '',
      timezone: typeof p.timezone === 'string' ? p.timezone : '',
      businessTypeId: typeof p.businessTypeId === 'number' ? p.businessTypeId : null,
      customBusinessType: typeof p.customBusinessType === 'string' ? p.customBusinessType : '',
    };
  } catch {
    return null;
  }
}

export function clearDraft(): void {
  sessionStorage.removeItem(DRAFT_KEY);
}

export function useDraftPersistence(formData: OnboardingFormData): void {
  const isFirstRender = useRef(true);

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }

    try {
      if (formData === EMPTY_FORM) {
        sessionStorage.removeItem(DRAFT_KEY);
      } else {
        sessionStorage.setItem(DRAFT_KEY, JSON.stringify(formData));
      }
    } catch {
      /* quota exceeded */
    }
  }, [formData]);
}
