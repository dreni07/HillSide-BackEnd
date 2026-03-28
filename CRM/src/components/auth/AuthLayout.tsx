import type { ReactNode } from 'react';
import { MouseGlow } from '../MouseGlow';

type AuthPageProps = { children: ReactNode; className?: string };

export function AuthPage({ children, className }: AuthPageProps) {
  return (
    <MouseGlow className={['landing-v2', 'landing-auth', className].filter(Boolean).join(' ')}>
      {/* Landing background (grid + mouse glow) */}
      <div className="landing-v2-bg" aria-hidden>
        <div className="landing-v2-bg-grid" aria-hidden />
      </div>
      <main className="landing-v2-main">{children}</main>
    </MouseGlow>
  );
}

type AuthCardProps = { children: ReactNode; className?: string };

export function AuthCard({ children, className }: AuthCardProps) {
  return <div className={['landing-auth-card', className].filter(Boolean).join(' ')}>{children}</div>;
}

