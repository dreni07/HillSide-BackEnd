import { useOnboarding } from '../../context/OnboardingContext';
import { TIMEZONES } from '../../constants/onboarding';

export function TimezoneSelect() {
  const { state, dispatch } = useOnboarding();
  const { formData, isSubmitting } = state;

  return (
    <label>
      Zona kohore
      <select
        value={formData.timezone}
        onChange={(e) => dispatch({ type: 'SET_FIELD', field: 'timezone', value: e.target.value })}
        disabled={isSubmitting}
      >
        <option value="">Zgjidhni zonën kohore</option>
        {TIMEZONES.map((tz) => (
          <option key={tz.value} value={tz.value}>{tz.label}</option>
        ))}
      </select>
    </label>
  );
}
