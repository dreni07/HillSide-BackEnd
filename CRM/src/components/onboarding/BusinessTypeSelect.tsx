import { useCallback, useMemo } from 'react';
import { useOnboarding } from '../../context/OnboardingContext';
import { useBusinessTypes } from '../../hooks/useBusinessTypes';
import { validateSingleField } from '../../hooks/useOnboardingSubmit';

/** Renditje fikse: Ecommerce, pastaj Service, pastaj çdo gjë tjetër nga API. */
function sortBusinessTypes<T extends { slug: string; name: string }>(list: T[]): T[] {
  const rank = (slug: string) => {
    if (slug === 'ecommerce') return 0;
    if (slug === 'service') return 1;
    return 99;
  };
  return [...list].sort((a, b) => {
    const d = rank(a.slug) - rank(b.slug);
    return d !== 0 ? d : a.name.localeCompare(b.name);
  });
}

export function BusinessTypeSelect() {
  const { state, dispatch, hasTypeSelection } = useOnboarding();
  const { formData, fieldErrors, isSubmitting } = state;
  const { types, loading, error: fetchError } = useBusinessTypes();

  const options = useMemo(
    () => sortBusinessTypes(Object.values(types).flat()),
    [types],
  );

  const handleBlur = useCallback(() => {
    const error = validateSingleField('businessTypeId', formData, hasTypeSelection);
    dispatch({ type: 'SET_FIELD_ERROR', field: 'businessTypeId', error });
  }, [formData, hasTypeSelection, dispatch]);

  const handleChange = useCallback(
    (val: string) => {
      dispatch({ type: 'SELECT_REAL_TYPE', id: val ? Number(val) : null });
    },
    [dispatch],
  );

  return (
    <label>
      Lloji i biznesit <span className="required" aria-hidden="true">*</span>

      {loading ? (
        <p className="onboarding-inline-msg onboarding-inline-msg--muted">…</p>
      ) : fetchError ? (
        <p className="onboarding-inline-msg onboarding-inline-msg--error">{fetchError}</p>
      ) : (
        <select
          value={formData.businessTypeId?.toString() ?? ''}
          onChange={(e) => handleChange(e.target.value)}
          onBlur={handleBlur}
          disabled={isSubmitting}
          required
          aria-label="Lloji i biznesit"
        >
          <option value="" disabled>
            Zgjidhni llojin e biznesit
          </option>
          {options.map((bt) => (
            <option key={bt.id} value={bt.id}>
              {bt.name}
            </option>
          ))}
        </select>
      )}

      {fieldErrors.businessTypeId && (
        <span className="field-error" role="status">{fieldErrors.businessTypeId}</span>
      )}
    </label>
  );
}
