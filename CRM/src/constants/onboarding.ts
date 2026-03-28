import type { OnboardingFormData } from '../types/onboarding';

export const DRAFT_KEY = 'companyOnboardingDraft';

export const EMPTY_FORM: OnboardingFormData = {
  companyName: '',
  description: '',
  phone: '',
  email: '',
  address: '',
  website: '',
  timezone: '',
  businessTypeId: null,
  customBusinessType: '',
};

export const TIMEZONES = [
  { value: 'Europe/Tirane', label: 'Europe/Tiranë (CET)' },
  { value: 'Europe/London', label: 'Europe/London (GMT)' },
  { value: 'Europe/Berlin', label: 'Europe/Berlin (CET)' },
  { value: 'Europe/Rome', label: 'Europe/Rome (CET)' },
  { value: 'Europe/Zurich', label: 'Europe/Zurich (CET)' },
  { value: 'America/New_York', label: 'America/New York (EST)' },
  { value: 'America/Chicago', label: 'America/Chicago (CST)' },
  { value: 'America/Los_Angeles', label: 'America/Los Angeles (PST)' },
  { value: 'Asia/Istanbul', label: 'Asia/Istanbul (TRT)' },
  { value: 'Asia/Dubai', label: 'Asia/Dubai (GST)' },
] as const;

export const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
export const URL_RE = /^https?:\/\/.+/;

/** Numër minimal shifrash (pas heqjes së jo-shifrave) për telefon biznesi në onboarding. */
export const PHONE_MIN_DIGITS = 8;
export const PHONE_MAX_DIGITS = 15;

export const DESCRIPTION_MIN_LEN = 10;
export const ADDRESS_MIN_LEN = 5;
