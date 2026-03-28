import { createPortal } from 'react-dom';
import { Loader2 } from 'lucide-react';

export interface LoadingOverlayProps {
  open: boolean;
  /** Short heading, e.g. “Duke ruajtur…” */
  title?: string;
  /** Optional supporting line */
  description?: string;
}

/**
 * Full-screen blocking loader (reusable for saves, bootstraps, etc.).
 */
export function LoadingOverlay({
  open,
  title = 'Duke përpunuar…',
  description,
}: LoadingOverlayProps) {
  if (!open) return null;

  return createPortal(
    <div className="studio-loading-overlay" role="alertdialog" aria-busy="true" aria-live="polite">
      <div className="studio-loading-card">
        <Loader2 className="studio-loading-spinner" size={36} aria-hidden />
        <p className="studio-loading-title">{title}</p>
        {description ? <p className="studio-loading-desc">{description}</p> : null}
      </div>
    </div>,
    document.body,
  );
}
