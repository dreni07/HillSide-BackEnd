import { useOnboarding } from '../../context/OnboardingContext';
import { isOnboardingFormReady } from '../../hooks/useOnboardingSubmit';

export function OnboardingActions() {
  const { state, hasTypeSelection } = useOnboarding();
  const { formData, isSubmitting } = state;

  const canSubmit = !isSubmitting && isOnboardingFormReady(formData, hasTypeSelection);

  return (
    <div className="company-onboarding-actions">
      <button type="submit" className="btn-primary" disabled={!canSubmit}>
        {isSubmitting ? 'Duke ruajtur…' : 'Vazhdo në panel'}
      </button>
    </div>
  );
}
