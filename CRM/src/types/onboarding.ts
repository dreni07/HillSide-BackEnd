import type { Dispatch } from 'react';
import type { BusinessType } from './api';

export type OnboardingFormData = {
  companyName: string;
  description: string;
  phone: string;
  email: string;
  address: string;
  website: string;
  timezone: string;
  businessTypeId: number | null;
  customBusinessType: string;
};

export type OnboardingState = {
  formData: OnboardingFormData;
  fieldErrors: Record<string, string>;
  isSubmitting: boolean;
  submitError: string | null;
};

export type OnboardingAction =
  | { type: 'SET_FIELD'; field: keyof OnboardingFormData; value: string | number | null }
  | { type: 'SET_FORM_DATA'; data: OnboardingFormData }
  | { type: 'SET_FIELD_ERROR'; field: string; error: string | null }
  | { type: 'SET_FIELD_ERRORS'; errors: Record<string, string> }
  | { type: 'CLEAR_FIELD_ERROR'; field: string }
  | { type: 'SET_SUBMITTING'; value: boolean }
  | { type: 'SET_SUBMIT_ERROR'; error: string | null }
  | { type: 'SELECT_OTHER_TYPE'; name: string }
  | { type: 'SELECT_REAL_TYPE'; id: number | null }
  | { type: 'RESET' };

export interface OnboardingContextValue {
  state: OnboardingState;
  dispatch: Dispatch<OnboardingAction>;
  isOtherSelected: boolean;
  hasTypeSelection: boolean;
  setField: (field: keyof OnboardingFormData, value: string | number | null) => void;
}

export type GroupedBusinessTypes = Record<string, BusinessType[]>;
