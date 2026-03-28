import { useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { usePasswordResetFlow } from '../context/PasswordResetContext';
import { useForm } from '../hooks/useForm';
import type { VerifyCodeFormValues } from '../types/passwordReset';
import { VerificationCodeView } from '../components/auth/password-reset/VerificationCodeView';
import type { FormEvent } from 'react';
import { getStoredUser } from '../services/api';

export function VerificationCode() {
  const navigate = useNavigate();
  const {
    email,
    step,
    verifyCode,
    resendCode,
    requestReset,
    loading,
    lastError,
    lastSuccess,
    clearMessages,
    expectedOtp,
  } =
    usePasswordResetFlow();

  const form = useForm<VerifyCodeFormValues>({ code: '' });

  useEffect(() => {
    clearMessages();
  }, [clearMessages]);

  const isReady = step === 'code_sent' && email;
  const autoRequestedRef = useRef(false);

  useEffect(() => {
    // Nëse përdoruesi vjen direkt këtu (p.sh. nga login/register), bëjmë auto "request" për OTP mock.
    if (autoRequestedRef.current) return;
    if (isReady) return;
    const storedUser = getStoredUser();
    const storedEmail = storedUser?.email;
    if (!storedEmail) return;

    autoRequestedRef.current = true;
    requestReset(storedEmail).catch(() => {
      // lastError nga context do shfaqet.
      autoRequestedRef.current = false; // lejoj "resend" manual
    });
  }, [isReady, requestReset]);

  function validateCode(): { code?: string } {
    const code = form.values.code.trim();
    if (!code) return { code: 'Kodi është i detyrueshëm.' };
    if (!/^\d{6}$/.test(code)) return { code: 'Kodi duhet të jetë saktësisht 6 shifra.' };
    return {};
  }

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();

    if (!isReady) return;

    const errs = validateCode();
    form.setErrors(errs);
    if (Object.keys(errs).length > 0) return;

    try {
      await verifyCode(form.values.code);
      navigate('/reset-password');
    } catch {
      // lastError from context will be rendered.
    }
  }

  const codeError = form.errors.code ?? null;
  const submitDisabled = !isReady;

  async function handleResend() {
    try {
      await resendCode();
      // Stay on same page; context sets step to `code_sent`.
    } catch {
      // lastError from context will be rendered.
    }
  }

  return (
    <VerificationCodeView
      email={email}
      code={form.values.code}
      codeError={codeError}
      generalError={lastError}
      generalSuccess={lastSuccess}
      testOtp={expectedOtp}
      loading={loading.verifying}
      resendLoading={loading.requesting}
      submitDisabled={submitDisabled}
      onCodeChange={(value) => form.setField('code', value)}
      onSubmit={handleSubmit}
      onResend={handleResend}
    />
  );
}

