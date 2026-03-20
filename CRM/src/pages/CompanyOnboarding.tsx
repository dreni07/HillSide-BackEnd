import { type FormEvent, useCallback } from 'react';
import { OnboardingProvider } from '../context/OnboardingContext';
import { readDraft, useDraftPersistence } from '../hooks/useDraftPersistence';
import { useOnboardingSubmit } from '../hooks/useOnboardingSubmit';
import { useOnboarding } from '../context/OnboardingContext';
import {
  CompanyInfoFields,
  BusinessTypeSelect,
  ContactFields,
  TimezoneSelect,
  OnboardingActions,
} from '../components/onboarding';

function OnboardingForm() {
  const { state } = useOnboarding();
  const { handleSubmit } = useOnboardingSubmit();

  useDraftPersistence(state.formData);

  const onSubmit = useCallback(
    (e: FormEvent) => {
      e.preventDefault();
      handleSubmit();
    },
    [handleSubmit],
  );

  return (
    <div className="auth-page">
      <div className="auth-card company-onboarding-card">
        <h1>Na tregoni për biznesin tuaj</h1>
        <p className="auth-hint company-onboarding-hint">
          Këto të dhëna ndihmojnë sistemin dhe inteligjencën artificiale të kuptojnë më mirë biznesin tuaj
          dhe të japin rekomandime më të sakta.
        </p>

        {state.submitError && (
          <div className="auth-error" role="alert">{state.submitError}</div>
        )}

        <form className="company-onboarding-form" onSubmit={onSubmit}>
          <CompanyInfoFields />
          <BusinessTypeSelect />
          <ContactFields />
          <TimezoneSelect />
          <OnboardingActions />
        </form>
      </div>
    </div>
  );
}

export function CompanyOnboarding() {
  const draft = readDraft();

  return (
    <OnboardingProvider initialData={draft ?? undefined}>
      <OnboardingForm />
    </OnboardingProvider>
  );
}
