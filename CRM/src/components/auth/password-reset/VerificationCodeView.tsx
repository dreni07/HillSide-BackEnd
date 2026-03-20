import { Link } from 'react-router-dom';
import { AuthCard, AuthPage } from '../AuthLayout';
import { AuthForm } from '../AuthForm';
import { AuthError, AuthSuccess } from '../AuthMessages';
import type { FormEvent } from 'react';

export type VerificationCodeViewProps = {
  email: string | null;
  code: string;
  codeError?: string | null;
  generalError?: string | null;
  generalSuccess?: string | null;
  testOtp?: string | null;
  loading?: boolean;
  resendLoading?: boolean;
  submitDisabled?: boolean;
  onCodeChange: (code: string) => void;
  onSubmit: (e: FormEvent) => void;
  onResend: () => void;
};

export function VerificationCodeView({
  email,
  code,
  codeError,
  generalError,
  generalSuccess,
  testOtp,
  loading,
  resendLoading,
  submitDisabled,
  onCodeChange,
  onSubmit,
  onResend,
}: VerificationCodeViewProps) {
  return (
    <AuthPage>
      <AuthCard>
        <AuthForm
          title="Verifiko kodin"
          hint={
            email
              ? `Vendos kodin 6-shifror të dërguar në: ${email} (mock).`
              : 'Vendos kodin që të është dërguar (mock).'
          }
          onSubmit={onSubmit}
        >
          <AuthError message={generalError} />
          <AuthSuccess message={generalSuccess} />

          {testOtp ? (
            <p className="auth-hint" style={{ marginTop: '0.75rem' }}>
              Për test (mock): kodi është <b>{testOtp}</b>
            </p>
          ) : null}

          <AuthForm.Field label="Kodi (OTP)" required error={codeError ?? null}>
            <input
              type="text"
              inputMode="numeric"
              pattern="[0-9]*"
              value={code}
              onChange={(e) => onCodeChange(e.target.value)}
              placeholder="p.sh. 123456"
              autoComplete="one-time-code"
              required
            />
          </AuthForm.Field>

          <button type="submit" disabled={loading || submitDisabled}>
            {loading ? 'Duke verifikuar…' : 'Verifiko'}
          </button>

          <button
            type="button"
            className="auth-resend-btn"
            disabled={resendLoading || loading}
            onClick={onResend}
          >
            {resendLoading ? 'Duke dërguar kodin…' : 'Resend code'}
          </button>

          <p className="auth-footer">
            Nuk ke kod?{' '}
            <Link to="/forgot-password" aria-label="Kthehu te Forgot Password">
              Kthehu
            </Link>
          </p>
        </AuthForm>
      </AuthCard>
    </AuthPage>
  );
}

