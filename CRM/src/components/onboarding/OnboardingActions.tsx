import { useOnboarding } from '../../context/OnboardingContext';
import { useOnboardingSubmit } from '../../hooks/useOnboardingSubmit';

export function OnboardingActions() {
  const { state, hasTypeSelection } = useOnboarding();
  const { formData, isSubmitting } = state;
  const { handleSkip } = useOnboardingSubmit();

  const canSubmit = !isSubmitting && !!formData.companyName.trim() && hasTypeSelection;

  return (
    <div className="company-onboarding-actions">
      <button type="button" className="btn-secondary" onClick={handleSkip} disabled={isSubmitting}>
        Më vonë
      </button>
      <button type="submit" className="btn-primary" disabled={!canSubmit}>
        {isSubmitting ? 'Duke ruajtur…' : 'Vazhdo në panel'}
      </button>
    </div>
  );
}
