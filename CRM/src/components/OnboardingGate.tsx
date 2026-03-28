import { Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

/**
 * Bllokon `/app` për klientët që nuk kanë përfunduar onboarding (sipas backend).
 * Adminët kalojnë gjithmonë.
 */
export function OnboardingGate({ children }: { children: React.ReactNode }) {
  const { user, loading } = useAuth();

  if (loading) {
    return <div className="auth-loading">Duke ngarkuar…</div>;
  }

  if (!user) {
    return <div className="auth-loading">Duke ngarkuar…</div>;
  }

  if (user.role === 'admin') {
    return <>{children}</>;
  }

  if (user && !user.onboarding_completed) {
    return <Navigate to="/onboarding/company" replace />;
  }

  return <>{children}</>;
}
