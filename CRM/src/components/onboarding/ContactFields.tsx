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
        Telefoni <span className="required" aria-hidden="true">*</span>
        <input
          type="tel"
          value={formData.phone}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'phone', value: e.target.value })}
          onBlur={() => handleBlur('phone')}
          disabled={isSubmitting}
          required
          placeholder="p.sh. +355 69 123 4567"
        />
        {fieldErrors.phone && (
          <span className="field-error" role="status">{fieldErrors.phone}</span>
        )}
      </label>

      <label>
        Email i biznesit <span className="required" aria-hidden="true">*</span>
        <input
          type="email"
          value={formData.email}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'email', value: e.target.value })}
          onBlur={() => handleBlur('email')}
          disabled={isSubmitting}
          required
          placeholder="p.sh. info@kompania.com"
        />
        {fieldErrors.email && (
          <span className="field-error" role="status">{fieldErrors.email}</span>
        )}
      </label>

      <label>
        Adresa <span className="required" aria-hidden="true">*</span>
        <input
          type="text"
          value={formData.address}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'address', value: e.target.value })}
          onBlur={() => handleBlur('address')}
          disabled={isSubmitting}
          required
          placeholder="p.sh. Rruga e Durrësit, Tiranë"
        />
        {fieldErrors.address && (
          <span className="field-error" role="status">{fieldErrors.address}</span>
        )}
      </label>

      <label>
        Website <span className="required" aria-hidden="true">*</span>
        <input
          type="url"
          value={formData.website}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'website', value: e.target.value })}
          onBlur={() => handleBlur('website')}
          disabled={isSubmitting}
          required
          placeholder="https://www.kompania.com"
        />
        {fieldErrors.website && (
          <span className="field-error" role="status">{fieldErrors.website}</span>
        )}
      </label>
    </>
  );
}
