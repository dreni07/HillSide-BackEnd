import type { FormEvent } from 'react';
import { Link } from 'react-router-dom';
import { AuthCard, AuthPage } from '../AuthLayout';
import { AuthForm } from '../AuthForm';
import { AuthError, AuthSuccess } from '../AuthMessages';

export type ForgotPasswordViewProps = {
  email: string;
  emailError?: string | null;
  generalError?: string | null;
  generalSuccess?: string | null;
  testOtp?: string | null;
  loading?: boolean;
  onEmailChange: (email: string) => void;
  onSubmit: (e: FormEvent) => void;
};

export function ForgotPasswordView({
  email,
  emailError,
  generalError,
  generalSuccess,
  testOtp,
  loading,
  onEmailChange,
  onSubmit,
}: ForgotPasswordViewProps) {
  return (
    <AuthPage>
      <AuthCard>
        <AuthForm title="Ke harruar fjalëkalimin?" hint="Shkruani emailin. Ne do të dërgojmë një kod verifikimi (mock)."
          onSubmit={onSubmit}
        >
          <AuthError message={generalError} />
          <AuthSuccess message={generalSuccess} />

          {testOtp ? (
            <p className="auth-hint" style={{ marginTop: '0.75rem' }}>
              Për test (mock): kodi juaj është <b>{testOtp}</b>
            </p>
          ) : null}

          <AuthForm.Field label="Email" required error={emailError ?? null}>
            <input
              type="email"
              value={email}
              onChange={(e) => onEmailChange(e.target.value)}
              placeholder="p.sh. demo@email.com"
              autoComplete="email"
              required
            />
          </AuthForm.Field>

          <button type="submit" disabled={loading}>
            {loading ? 'Duke dërguar…' : 'Dërgo request'}
          </button>

          <p className="auth-footer">
            Keni llogari? <Link to="/login">Hyni</Link>
          </p>
        </AuthForm>
      </AuthCard>
    </AuthPage>
  );
}

