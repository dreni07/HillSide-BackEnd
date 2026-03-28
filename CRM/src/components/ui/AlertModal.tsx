import { Modal } from '../Modal';

export type AlertModalVariant = 'success' | 'error' | 'info';

export interface AlertModalProps {
  open: boolean;
  onClose: () => void;
  variant: AlertModalVariant;
  title: string;
  message: string;
  /** Primary action label (defaults by variant) */
  confirmLabel?: string;
}

/**
 * Result / error dialog built on the shared `Modal` shell (hm-* styles).
 */
export function AlertModal({
  open,
  onClose,
  variant,
  title,
  message,
  confirmLabel,
}: AlertModalProps) {
  const label =
    confirmLabel ??
    (variant === 'error' ? 'Kuptova' : variant === 'success' ? 'Shkëlqyeshëm' : 'Në rregull');

  const subtitle =
    variant === 'success'
      ? 'Veprimi përfundoi.'
      : variant === 'error'
        ? 'Diçka shkoi keq.'
        : undefined;

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={title}
      subtitle={subtitle}
      footer={
        <button type="button" className="hm-btn hm-btn-primary" onClick={onClose}>
          {label}
        </button>
      }
    >
      <p className={`alert-modal-message alert-modal-message--${variant}`}>{message}</p>
    </Modal>
  );
}
