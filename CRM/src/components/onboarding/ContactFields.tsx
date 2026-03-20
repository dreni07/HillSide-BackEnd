import { useCallback } from 'react';
import { useOnboarding } from '../../context/OnboardingContext';
import { validateSingleField } from '../../hooks/useOnboardingSubmit';

export function ContactFields() {
  const { state, dispatch, hasTypeSelection } = useOnboarding();
  const { formData, fieldErrors, isSubmitting } = state;

  const handleBlur = useCallback(
    (field: string) => {
      const error = validateSingleField(field, formData, hasTypeSelection);
      dispatch({ type: 'SET_FIELD_ERROR', field, error });
    },
    [formData, hasTypeSelection, dispatch],
  );

  return (
    <>
      <label>
        Telefoni
        <input
          type="tel"
          value={formData.phone}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'phone', value: e.target.value })}
          disabled={isSubmitting}
          placeholder="p.sh. +355 69 123 4567"
        />
      </label>

      <label>
        Email i biznesit
        <input
          type="email"
          value={formData.email}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'email', value: e.target.value })}
          onBlur={() => handleBlur('email')}
          disabled={isSubmitting}
          placeholder="p.sh. info@kompania.com"
        />
        {fieldErrors.email && (
          <span className="field-error" role="status">{fieldErrors.email}</span>
        )}
      </label>

      <label>
        Adresa
        <input
          type="text"
          value={formData.address}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'address', value: e.target.value })}
          disabled={isSubmitting}
          placeholder="p.sh. Rruga e Durrësit, Tiranë"
        />
      </label>

      <label>
        Website
        <input
          type="url"
          value={formData.website}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'website', value: e.target.value })}
          onBlur={() => handleBlur('website')}
          disabled={isSubmitting}
          placeholder="https://www.kompania.com"
        />
        {fieldErrors.website && (
          <span className="field-error" role="status">{fieldErrors.website}</span>
        )}
      </label>
    </>
  );
}
