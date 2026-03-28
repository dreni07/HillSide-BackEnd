import { Component, type ErrorInfo, type ReactNode } from 'react';

interface Props {
  children: ReactNode;
}

interface State {
  error: Error | null;
}

/**
 * Surfaces render errors in the DOM so a blank `#root` is easier to diagnose.
 */
export class RootErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    console.error('Root render error:', error, info.componentStack);
  }

  render(): ReactNode {
    if (this.state.error) {
      return (
        <div className="root-error-boundary" style={{ padding: '2rem', maxWidth: '40rem', margin: '0 auto' }}>
          <h1 style={{ fontSize: '1.25rem', marginBottom: '0.75rem' }}>Ndodhi një gabim në aplikacion</h1>
          <p style={{ marginBottom: '0.5rem', color: '#64748b' }}>
            Hapni konsolën e zhvilluesit (F12) për detaje. Mesazhi:
          </p>
          <pre
            style={{
              padding: '1rem',
              background: '#f1f5f9',
              borderRadius: '8px',
              overflow: 'auto',
              fontSize: '0.875rem',
            }}
          >
            {this.state.error.message}
          </pre>
        </div>
      );
    }
    return this.props.children;
  }
}
