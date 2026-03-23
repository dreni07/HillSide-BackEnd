import type { AutomationOrder } from '../../types/ordersAutomation';

interface OrderDetailModalProps {
  order: AutomationOrder | null;
  open: boolean;
  onClose: () => void;
}

export function OrderDetailModal({ order, open, onClose }: OrderDetailModalProps) {
  if (!open || !order) return null;

  return (
    <div className="modal-overlay" onClick={onClose} role="dialog" aria-modal="true" aria-labelledby="order-detail-title">
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="modal-header">
          <h2 id="order-detail-title">Detajet e porosisë</h2>
          <button type="button" className="modal-close" onClick={onClose} aria-label="Mbyll">
            ×
          </button>
        </div>
        <div className="modal-form">
          <dl className="orders-dl">
            <div className="orders-dl-row">
              <dt>ID</dt>
              <dd>
                <code className="orders-code">{order.id}</code>
              </dd>
            </div>
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
            <div className="orders-dl-row">
              <dt>Statusi</dt>
              <dd>{order.status}</dd>
            </div>
            {order.trackingNumber && (
              <div className="orders-dl-row">
                <dt>Gjurmimi</dt>
                <dd>
                  <code className="orders-code">{order.trackingNumber}</code>
                </dd>
              </div>
            )}
          </dl>
          <div className="modal-actions">
            <button type="button" className="btn-primary" onClick={onClose}>
              Mbyll
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
