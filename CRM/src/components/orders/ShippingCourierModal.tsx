import type { AutomationOrder } from '../../types/ordersAutomation';

interface ShippingCourierModalProps {
  order: AutomationOrder | null;
  open: boolean;
  loading: boolean;
  onClose: () => void;
  onConfirm: () => void;
}

export function ShippingCourierModal({ order, open, loading, onClose, onConfirm }: ShippingCourierModalProps) {
  if (!open || !order) return null;

  return (
    <div className="modal-overlay" onClick={onClose} role="dialog" aria-modal="true" aria-labelledby="shipping-modal-title">
      <div className="modal-content orders-shipping-modal" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2 id="shipping-modal-title">Dërgo te korrieri</h2>
          <button type="button" className="modal-close" onClick={onClose} aria-label="Mbyll">
            ×
          </button>
        </div>
        <div className="modal-form">
          <p className="orders-shipping-hint">Të dhënat e klientit dhe produkti (simulim — pa API real).</p>
          <dl className="orders-dl orders-dl--compact">
            <div className="orders-dl-row">
              <dt>Klienti</dt>
              <dd>{order.customerName}</dd>
            </div>
            <div className="orders-dl-row">
              <dt>Telefon</dt>
              <dd>{order.phone}</dd>
            </div>
            <div className="orders-dl-row">
              <dt>Adresa</dt>
              <dd>{order.address}</dd>
            </div>
            <div className="orders-dl-row">
              <dt>Produkti</dt>
              <dd>{order.product}</dd>
            </div>
          </dl>
          <div className="modal-actions">
            <button type="button" className="btn-secondary" onClick={onClose} disabled={loading}>
              Anulo
            </button>
            <button type="button" className="btn-primary" onClick={onConfirm} disabled={loading}>
              {loading ? 'Duke dërguar…' : 'Konfirmo dërgesën'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
