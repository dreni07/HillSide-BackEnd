import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { getStoredUser } from '../services/api';
import { GoogleContinueButton } from '../components/auth/GoogleContinueButton';
import { AuthCard, AuthPage } from '../components/auth/AuthLayout';

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [oauthMessage, setOauthMessage] = useState<string | null>(null);
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: { pathname?: string } } | null)?.from?.pathname;

  useEffect(() => {
    const savedEmail = localStorage.getItem('crm_login_remember_email');
    if (!savedEmail) return;
    setEmail(savedEmail);
    setRememberMe(true);
  }, []);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await login(email, password);
      if (rememberMe) {
        localStorage.setItem('crm_login_remember_email', email.trim());
      } else {
        localStorage.removeItem('crm_login_remember_email');
      }
      const user = getStoredUser();
      const target = from && from.startsWith('/app') ? from : undefined;
      if (user?.role === 'admin') {
        navigate(target ?? '/app/klientet', { replace: true });
      } else {
        navigate(target ?? '/app', { replace: true });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gabim në hyrje.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthPage>
      <AuthCard>
        <h1>Hyr në CRM</h1>
        <form onSubmit={handleSubmit}>
          {error && <div className="auth-error" role="alert">{error}</div>}
          <label>
            Email
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
            />
          </label>
          <label>
            Fjalëkalim
            <input
              type={showPassword ? 'text' : 'password'}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
            />
            <label className="auth-show-password">
              <input
                type="checkbox"
                checked={showPassword}
                onChange={(e) => setShowPassword(e.target.checked)}
                aria-label="Shfaq fjalëkalimin"
              />
              Shfaq fjalëkalimin
            </label>
          </label>
          <button type="submit" disabled={loading}>
            {loading ? 'Duke hyrë…' : 'Hyr'}
          </button>

          <label className="auth-remember">
            <input
              type="checkbox"
              checked={rememberMe}
              onChange={(e) => setRememberMe(e.target.checked)}
            />
            Më mbaj të kyçur
          </label>

          <div className="auth-divider">ose</div>

          <GoogleContinueButton
            disabled={loading}
            onClick={() => {
              setOauthMessage('Demo: vazhdo me Google (UI vetëm). Integrimi real do të shtohet më vonë.');
            }}
            label="Continue with Google"
          />

          {oauthMessage && <div className="auth-hint" style={{ marginTop: '0.75rem' }}>{oauthMessage}</div>}
        </form>
        <p className="auth-footer">
          <Link to="/forgot-password">Keni harruar fjalëkalimin?</Link>
          <span style={{ margin: '0 0.5rem' }}>·</span>
          Nuk keni llogari? <Link to="/register">Regjistrohuni</Link>
        </p>
      </AuthCard>
    </AuthPage>
  );
}
