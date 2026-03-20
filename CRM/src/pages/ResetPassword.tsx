import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import type { FormEvent } from 'react';
import { usePasswordResetFlow } from '../context/PasswordResetContext';
import { useForm } from '../hooks/useForm';
import type { ResetPasswordFormValues } from '../types/passwordReset';
import { ResetPasswordView } from '../components/auth/password-reset/ResetPasswordView';

export function ResetPassword() {
  const navigate = useNavigate();
  const { step, resetPassword, loading, lastError, lastSuccess, clearMessages } = usePasswordResetFlow();
  const [redirecting, setRedirecting] = useState(false);

  const form = useForm<ResetPasswordFormValues>({ password: '', confirmPassword: '' });

  useEffect(() => {
    clearMessages();
  }, [clearMessages]);

  const isReady = step === 'verified';

  function validate(): Partial<Record<keyof ResetPasswordFormValues, string>> {
    const password = form.values.password;
    const confirmPassword = form.values.confirmPassword;

    const errs: Partial<Record<keyof ResetPasswordFormValues, string>> = {};
    if (!password || password.length < 8) errs.password = 'Fjalëkalimi duhet të ketë së paku 8 karaktere.';
    if (!confirmPassword) errs.confirmPassword = 'Konfirmimi është i detyrueshëm.';
    if (password && confirmPassword && password !== confirmPassword) errs.confirmPassword = 'Fjalëkalimet nuk përputhen.';
    return errs;
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (!isReady) return;

    const errs = validate();
    form.setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    try {
      setRedirecting(false);
      await resetPassword(form.values.password);
      setRedirecting(true);
      window.setTimeout(() => navigate('/login', { replace: true }), 800);
    } catch {
      // lastError from context will be rendered.
    }
  }

  const generalError = isReady ? lastError : lastSuccess ? null : 'Ju lutem verifikoni kodin përpara se me resetu fjalëkalimin.';

  return (
    <ResetPasswordView
      password={form.values.password}
      confirmPassword={form.values.confirmPassword}
      passwordError={form.errors.password ?? null}
      confirmPasswordError={form.errors.confirmPassword ?? null}
      generalError={generalError}
      generalSuccess={lastSuccess}
      loading={loading.resetting || redirecting}
      onPasswordChange={(v) => form.setField('password', v)}
      onConfirmPasswordChange={(v) => form.setField('confirmPassword', v)}
      onSubmit={handleSubmit}
    />
  );
}

