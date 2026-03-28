import { useCallback } from 'react';
import { useOnboarding } from '../../context/OnboardingContext';
import { TIMEZONES } from '../../constants/onboarding';
import { validateSingleField } from '../../hooks/useOnboardingSubmit';

export function TimezoneSelect() {
  const { state, dispatch, hasTypeSelection } = useOnboarding();
  const { formData, fieldErrors, isSubmitting } = state;

  const handleBlur = useCallback(() => {
    const error = validateSingleField('timezone', formData, hasTypeSelection);
    dispatch({ type: 'SET_FIELD_ERROR', field: 'timezone', error });
  }, [formData, hasTypeSelection, dispatch]);

  return (
    <label>
      Zona kohore <span className="required" aria-hidden="true">*</span>
      <select
        value={formData.timezone}
        onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'timezone', value: e.target.value })}
        onBlur={handleBlur}
        disabled={isSubmitting}
        required
        aria-label="Zona kohore"
      >
        <option value="" disabled>
          Zgjidhni zonën kohore
        </option>
        {TIMEZONES.map((tz) => (
          <option key={tz.value} value={tz.value}>{tz.label}</option>
        ))}
      </select>
      {fieldErrors.timezone && (
        <span className="field-error" role="status">{fieldErrors.timezone}</span>
      )}
    </label>
  );
}
