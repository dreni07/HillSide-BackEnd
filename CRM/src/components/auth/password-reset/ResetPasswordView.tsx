import { Link } from 'react-router-dom';
import { AuthCard, AuthPage } from '../AuthLayout';
import { AuthForm } from '../AuthForm';
import { AuthError, AuthSuccess } from '../AuthMessages';
import type { FormEvent } from 'react';

export type ResetPasswordViewProps = {
  password: string;
  confirmPassword: string;
  passwordError?: string | null;
  confirmPasswordError?: string | null;
  generalError?: string | null;
  generalSuccess?: string | null;
  loading?: boolean;
  onPasswordChange: (password: string) => void;
  onConfirmPasswordChange: (value: string) => void;
  onSubmit: (e: FormEvent) => void;
};

export function ResetPasswordView({
  password,
  confirmPassword,
  passwordError,
  confirmPasswordError,
  generalError,
  generalSuccess,
  loading,
  onPasswordChange,
  onConfirmPasswordChange,
  onSubmit,
}: ResetPasswordViewProps) {
  return (
    <AuthPage>
      <AuthCard>
        <AuthForm
          title="Rivendos fjalëkalimin"
          hint="Vendos fjalëkalim të ri. (Front-end mock)"
          onSubmit={onSubmit}
        >
          <AuthError message={generalError} />
          <AuthSuccess message={generalSuccess} />

          <AuthForm.Field label="Fjalëkalimi i ri" required error={passwordError ?? null}>
            <input
              type="password"
              value={password}
              onChange={(e) => onPasswordChange(e.target.value)}
              placeholder="Minimumi 8 karaktere"
              autoComplete="new-password"
              required
            />
          </AuthForm.Field>

          <AuthForm.Field label="Konfirmo fjalëkalimin" required error={confirmPasswordError ?? null}>
            <input
              type="password"
              value={confirmPassword}
              onChange={(e) => onConfirmPasswordChange(e.target.value)}
              placeholder="Njëjtë me fjalëkalimin"
              autoComplete="new-password"
              required
            />
          </AuthForm.Field>

          <button type="submit" disabled={loading}>
            {loading ? 'Duke resetuar…' : 'Reset'}
          </button>

          <p className="auth-footer">
            Keni llogari? <Link to="/login">Hyni</Link>
          </p>
        </AuthForm>
      </AuthCard>
    </AuthPage>
  );
}

