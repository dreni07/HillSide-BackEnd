import type { FormEvent, ReactNode } from 'react';

type AuthFormProps = {
  title: string;
  hint?: string;
  error?: ReactNode;
  children: ReactNode;
  onSubmit: (e: FormEvent) => void;
  loading?: boolean;
  actions?: ReactNode;
};

function AuthFormRoot({ title, hint, error, children, onSubmit, actions, loading }: AuthFormProps) {
  return (
    <form onSubmit={onSubmit}>
      <h1>{title}</h1>
      {hint && <p className="auth-hint">{hint}</p>}
      {error}
      {children}
      {actions}
      {loading && null}
    </form>
  );
}

type AuthFieldProps = {
  label: string;
  required?: boolean;
  error?: string | null;
  children: ReactNode;
};

function AuthField({ label, required, error, children }: AuthFieldProps) {
  return (
    <label>
      {label}
      {required && <span className="required">*</span>}
      {children}
      {error ? <span className="field-error">{error}</span> : null}
    </label>
  );
}

type AuthFormComponent = typeof AuthFormRoot & { Field: typeof AuthField };

export const AuthForm = Object.assign(AuthFormRoot, {
  Field: AuthField,
}) satisfies AuthFormComponent;

