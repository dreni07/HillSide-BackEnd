import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useReducer,
  type ReactNode,
} from 'react';
import { EMPTY_FORM } from '../constants/onboarding';
import type {
  OnboardingFormData,
  OnboardingState,
  OnboardingAction,
  OnboardingContextValue,
} from '../types/onboarding';

function reducer(state: OnboardingState, action: OnboardingAction): OnboardingState {
  switch (action.type) {
    case 'SET_FIELD':
      return {
        ...state,
        formData: { ...state.formData, [action.field]: action.value },
      };

    case 'SET_FORM_DATA':
      return { ...state, formData: action.data };

    case 'SET_FIELD_ERROR':
      return {
        ...state,
        fieldErrors: action.error
          ? { ...state.fieldErrors, [action.field]: action.error }
          : Object.fromEntries(
              Object.entries(state.fieldErrors).filter(([k]) => k !== action.field),
            ),
      };

    case 'SET_FIELD_ERRORS':
      return { ...state, fieldErrors: action.errors };

    case 'CLEAR_FIELD_ERROR': {
      const { [action.field]: _, ...rest } = state.fieldErrors;
      return { ...state, fieldErrors: rest };
    }

    case 'SET_SUBMITTING':
      return { ...state, isSubmitting: action.value };

    case 'SET_SUBMIT_ERROR':
      return { ...state, submitError: action.error };

    case 'SELECT_OTHER_TYPE':
      return {
        ...state,
        formData: { ...state.formData, businessTypeId: null, customBusinessType: action.name },
        fieldErrors: Object.fromEntries(
          Object.entries(state.fieldErrors).filter(([k]) => k !== 'businessTypeId'),
        ),
      };

    case 'SELECT_REAL_TYPE':
      return {
        ...state,
        formData: { ...state.formData, businessTypeId: action.id, customBusinessType: '' },
        fieldErrors: Object.fromEntries(
          Object.entries(state.fieldErrors).filter(([k]) => k !== 'businessTypeId'),
        ),
      };

    case 'RESET':
      return { formData: EMPTY_FORM, fieldErrors: {}, isSubmitting: false, submitError: null };

    default:
      return state;
  }
}

const OnboardingContext = createContext<OnboardingContextValue | null>(null);

export function OnboardingProvider({
  initialData,
  children,
}: {
  initialData?: OnboardingFormData;
  children: ReactNode;
}) {
  const [state, dispatch] = useReducer(reducer, {
    formData: initialData ?? EMPTY_FORM,
    fieldErrors: {},
    isSubmitting: false,
    submitError: null,
  });

  const { businessTypeId, customBusinessType } = state.formData;
  const isOtherSelected = businessTypeId === null && customBusinessType.length > 0;
  const hasTypeSelection = businessTypeId !== null || isOtherSelected;

  const setField = useCallback(
    (field: keyof OnboardingFormData, value: string | number | null) => {
      dispatch({ type: 'SET_FIELD', field, value });
    },
    [],
  );

  const value = useMemo<OnboardingContextValue>(
    () => ({ state, dispatch, isOtherSelected, hasTypeSelection, setField }),
    [state, isOtherSelected, hasTypeSelection, setField],
  );

  return (
    <OnboardingContext.Provider value={value}>{children}</OnboardingContext.Provider>
  );
}

export function useOnboarding(): OnboardingContextValue {
  const ctx = useContext(OnboardingContext);
  if (!ctx) throw new Error('useOnboarding duhet përdorur brenda OnboardingProvider');
  return ctx;
}
