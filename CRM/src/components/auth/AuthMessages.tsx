export function AuthError({ message }: { message: string | null | undefined }) {
  if (!message) return null;
  return (
    <div className="auth-error" role="alert">
      {message}
    </div>
  );
}

export function AuthSuccess({ message }: { message: string | null | undefined }) {
  if (!message) return null;
  return <div className="form-success">{message}</div>;
}

