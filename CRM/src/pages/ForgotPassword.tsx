import { useEffect } from 'react';
import type { FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { EMAIL_RE } from '../constants/onboarding';
import { usePasswordResetFlow } from '../context/PasswordResetContext';
import { useForm } from '../hooks/useForm';
import type { ForgotPasswordFormValues } from '../types/passwordReset';
import { ForgotPasswordView } from '../components/auth/password-reset/ForgotPasswordView';

export function ForgotPassword() {
  const navigate = useNavigate();
  const { requestReset, loading, lastError, lastSuccess, clearMessages, expectedOtp } = usePasswordResetFlow();

  const form = useForm<ForgotPasswordFormValues>({ email: '' });

  useEffect(() => {
    clearMessages();
  }, [clearMessages]);

  function validateEmail(): { email?: string } {
    const email = form.values.email.trim();
    if (!email) return { email: 'Email është i detyrueshëm.' };
    if (!EMAIL_RE.test(email)) return { email: 'Ju lutem vendosni një email të vlefshëm.' };
    return {};
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    const errs = validateEmail();
    form.setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    try {
      await requestReset(form.values.email);
      navigate('/verify-code');
    } catch {
      // lastError from context will be rendered.
    }
  }

  return (
    <ForgotPasswordView
      email={form.values.email}
      emailError={form.errors.email ?? null}
      generalError={lastError}
      generalSuccess={lastSuccess}
      testOtp={expectedOtp}
      loading={loading.requesting}
      onEmailChange={(value) => form.setField('email', value)}
      onSubmit={handleSubmit}
    />
  );
}

