import type { ReactNode } from 'react';
import { MouseGlow } from '../MouseGlow';

export function AuthPage({ children }: { children: ReactNode }) {
  return (
    <MouseGlow className="landing-v2 landing-auth">
      {/* Landing background (grid + mouse glow) */}
      <div className="landing-v2-bg" aria-hidden>
        <div className="landing-v2-bg-grid" aria-hidden />
      </div>
      <main className="landing-v2-main">{children}</main>
    </MouseGlow>
  );
}

export function AuthCard({ children }: { children: ReactNode }) {
  return <div className="landing-auth-card">{children}</div>;
}

