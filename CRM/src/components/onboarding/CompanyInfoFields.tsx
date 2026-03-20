import { useCallback } from 'react';
import { useOnboarding } from '../../context/OnboardingContext';
import { validateSingleField } from '../../hooks/useOnboardingSubmit';

export function CompanyInfoFields() {
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
        Emri i kompanisë <span className="required" aria-hidden="true">*</span>
        <input
          type="text"
          value={formData.companyName}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'companyName', value: e.target.value })}
          onBlur={() => handleBlur('companyName')}
          disabled={isSubmitting}
          required
          placeholder="p.sh. Fashionista Store"
        />
        {fieldErrors.companyName && (
          <span className="field-error" role="status">{fieldErrors.companyName}</span>
        )}
      </label>

      <label>
        Çfarë bën kompania juaj?
        <textarea
          value={formData.description}
          onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'description', value: e.target.value })}
          disabled={isSubmitting}
          rows={3}
          placeholder="P.sh. Shesim produkte kozmetike online në Shqipëri…"
        />
      </label>
    </>
  );
}
