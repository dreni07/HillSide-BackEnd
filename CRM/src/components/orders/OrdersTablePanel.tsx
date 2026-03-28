import type { AutomationOrder, OrderAutomationStatus } from '../../types/ordersAutomation';

const STATUS_LABEL: Record<OrderAutomationStatus, string> = {
  pending: 'Në pritje',
  ready: 'Gati',
  shipped: 'Dërguar',
};

interface OrdersTablePanelProps {
  orders: AutomationOrder[];
  onStatusChange: (orderId: string, status: OrderAutomationStatus) => void;
  onViewDetails: (orderId: string) => void;
  onSendCourier: (orderId: string) => void;
}

export function OrdersTablePanel({
  orders,
  onStatusChange,
  onViewDetails,
  onSendCourier,
}: OrdersTablePanelProps) {
  if (orders.length === 0) {
    return <p className="orders-empty-hint">Nuk ka porosi të aprovuara ende. Aprovoni një mesazh nga lista.</p>;
  }

  return (
    <div className="orders-table-wrap">
      <table className="table-automation orders-table">
        <thead>
          <tr>
            <th>Klienti</th>
            <th>Produkti</th>
            <th>Statusi</th>
            <th>Veprime</th>
          </tr>
        </thead>
        <tbody>
          {orders.map((o) => (
            <tr key={o.id}>
              <td>{o.customerName}</td>
              <td className="td-response">{o.product}</td>
              <td>
                <span className={`orders-status-pill orders-status-pill--${o.status}`}>{STATUS_LABEL[o.status]}</span>
                {o.trackingNumber && (
                  <div className="orders-tracking-mini">Gjurmim: {o.trackingNumber}</div>
                )}
              </td>
              <td>
                <div className="orders-cell-actions">
                  <select
                    className="orders-status-select"
                    value={o.status}
                    onChange={(ev) => onStatusChange(o.id, ev.target.value as OrderAutomationStatus)}
                    aria-label={`Ndrysho statusin për ${o.customerName}`}
                  >
                    <option value="pending">{STATUS_LABEL.pending}</option>
                    <option value="ready">{STATUS_LABEL.ready}</option>
                    <option value="shipped">{STATUS_LABEL.shipped}</option>
                  </select>
                  <button type="button" className="btn-secondary btn-sm" onClick={() => onViewDetails(o.id)}>
                    Shiko detajet
                  </button>
                  {o.status !== 'shipped' && (
                    <button type="button" className="btn-primary btn-sm" onClick={() => onSendCourier(o.id)}>
                      Dërgo te korrieri
                    </button>
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
