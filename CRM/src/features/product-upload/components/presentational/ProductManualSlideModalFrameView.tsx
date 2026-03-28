/**
 * Presentational: korniza e modalit që rrëshqet nga sipër (portal + overlay).
 * Përmbajtja (forma) kalon si fëmijë për ndarje të qartë container/presentational.
 */

import { useEffect, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

export interface ProductManualSlideModalFrameViewProps {
  open: boolean;
  title: string;
  subtitle: string;
  onClose: () => void;
  /** Zakonisht `<form>` me fusha dhe footer. */
  children: ReactNode;
  /** Ref në panel për fokus menaxhim / teste. */
  panelRef?: React.RefObject<HTMLDivElement | null>;
}

export function ProductManualSlideModalFrameView({
  open,
  title,
  subtitle,
  onClose,
  children,
  panelRef,
}: ProductManualSlideModalFrameViewProps) {
  const overlayRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    function onKey(e: KeyboardEvent) {
      if (e.key === 'Escape') onClose();
    }
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = '';
    };
  }, [open, onClose]);

  if (!open) return null;

  function handleOverlayClick(ev: React.MouseEvent) {
    if (ev.target === overlayRef.current) onClose();
  }

  return createPortal(
    <div
      ref={overlayRef}
      className="product-upload-slide-overlay"
      onClick={handleOverlayClick}
      role="presentation"
    >
      <div
        ref={panelRef}
        className="product-upload-slide-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="product-manual-title"
      >
        <div className="product-upload-slide-header">
          <div>
            <h2 id="product-manual-title" className="product-upload-slide-title">
              {title}
            </h2>
            <p className="product-upload-slide-subtitle">{subtitle}</p>
          </div>
          <button type="button" className="product-upload-slide-close" onClick={onClose} aria-label="Mbyll">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>
        {children}
      </div>
    </div>,
    document.body,
  );
}
