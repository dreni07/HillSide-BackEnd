import { useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import type { ModalProps } from '../types/modal';

export function Modal({ open, onClose, title, subtitle, children, footer, maxWidth = '26rem', style }: ModalProps) {
  const overlayRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;

    function handleKeyDown(e: KeyboardEvent) {
      if (e.key === 'Escape') onClose();
    }

    document.addEventListener('keydown', handleKeyDown);
    document.body.style.overflow = 'hidden';

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.body.style.overflow = '';
    };
  }, [open, onClose]);

  if (!open) return null;

  function handleOverlayClick(e: React.MouseEvent) {
    if (e.target === overlayRef.current) onClose();
  }

  return createPortal(
    <div
      ref={overlayRef}
      className="hm-overlay"
      onClick={handleOverlayClick}
    >
      <div
        className="hm-panel"
        style={{ maxWidth, ...style }}
        role="dialog"
        aria-modal="true"
        aria-label={title}
      >
        <div className="hm-header">
          <div>
            <h2 className="hm-title">{title}</h2>
            {subtitle && <p className="hm-subtitle">{subtitle}</p>}
          </div>
          <button className="hm-close" onClick={onClose} aria-label="Mbyll">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div className="hm-body">{children}</div>

        {footer && <div className="hm-footer">{footer}</div>}
      </div>
    </div>,
    document.body,
  );
}
