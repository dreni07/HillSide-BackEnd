import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useMessages } from '../../hooks/useMessages';
import { useOrders } from '../../hooks/useOrders';
import { AIExtractionPanel } from './AIExtractionPanel';
import { MessageDetailBlock } from './MessageDetailBlock';
import { MessagesListPanel } from './MessagesListPanel';
import { OrderDetailModal } from './OrderDetailModal';
import { OrdersPanel } from './OrdersPanel';
import { OrdersTablePanel } from './OrdersTablePanel';
import { ShippingCourierModal } from './ShippingCourierModal';

/**
 * Pamja e dashboard-it (presentational) — merr gjendjen nga context përmes hooks.
 */
export function OrdersDashboardView() {
  const {
    messages,
    selectedMessageId,
    selectedMessage,
    messagesLoading,
    actionLoading,
    isEditingExtraction,
    toast,
    pendingCount,
    selectMessage,
    patchExtraction,
    setEditing,
    approveOrder,
    rejectMessage,
    clearToast,
    reload,
  } = useMessages();

  const {
    orders,
    shippingOrderId,
    orderDetailId,
    actionLoading: ordersActionLoading,
    setOrderStatus,
    openShipping,
    closeShipping,
    confirmShipping,
    openOrderDetail,
  } = useOrders();

  const shippingOrder = shippingOrderId ? orders.find((o) => o.id === shippingOrderId) ?? null : null;
  const detailOrder = orderDetailId ? orders.find((o) => o.id === orderDetailId) ?? null : null;

  useEffect(() => {
    if (!toast) return;
    const t = window.setTimeout(() => clearToast(), 5200);
    return () => window.clearTimeout(t);
  }, [toast, clearToast]);

  if (messagesLoading) {
    return <div className="page-loading">Duke ngarkuar mesazhet…</div>;
  }

  return (
    <div className="page-orders-dashboard">
      <div className="page-orders-header">
        <div>
          <h1>Porosite</h1>
          <p className="page-orders-subtitle">
            Vetëm mesazhe që AI i njeh si porosi (jo pyetje të përgjithshme). Nxjerrje me AI dhe dërgesë (simulim).
            Në pritje: <strong>{pendingCount}</strong>
          </p>
        </div>
        <div className="page-orders-header-actions">
          <button type="button" className="btn-secondary" onClick={() => void reload()}>
            Rifresko listën
          </button>
          <Link to="/app" className="dashboard-shortcut">
            CRM Paneli
          </Link>
        </div>
      </div>

      {toast && (
        <div className={toast.type === 'success' ? 'form-success orders-toast' : 'auth-error orders-toast'} role="status">
          {toast.text}
          <button type="button" className="orders-toast-dismiss" onClick={clearToast} aria-label="Mbyll njoftimin">
            ×
          </button>
        </div>
      )}

      <div className="orders-dashboard-grid">
        <OrdersPanel>
          <OrdersPanel.Title>Mesazhet</OrdersPanel.Title>
          <OrdersPanel.Body>
            {messages.length === 0 ? (
              <p className="orders-empty-hint">Nuk ka mesazhe simuluese.</p>
            ) : (
              <MessagesListPanel
                messages={messages}
                selectedId={selectedMessageId}
                onSelect={(id) => selectMessage(id)}
              />
            )}
          </OrdersPanel.Body>
        </OrdersPanel>

        <OrdersPanel>
          <OrdersPanel.Title>Detaji &amp; AI</OrdersPanel.Title>
          <OrdersPanel.Body>
            {!selectedMessage ? (
              <p className="orders-empty-hint">Zgjidhni një mesazh nga lista.</p>
            ) : (
              <>
                <MessageDetailBlock message={selectedMessage} />
                <AIExtractionPanel
                  message={selectedMessage}
                  isEditing={isEditingExtraction}
                  actionLoading={actionLoading}
                  onPatch={(patch) => patchExtraction(selectedMessage.id, patch)}
                  onToggleEdit={() => setEditing(!isEditingExtraction)}
                  onApprove={() => void approveOrder()}
                  onReject={() => void rejectMessage()}
                />
              </>
            )}
          </OrdersPanel.Body>
        </OrdersPanel>
      </div>

      <OrdersPanel>
        <OrdersPanel.Title>Porositë e aprovuara</OrdersPanel.Title>
        <OrdersPanel.Body>
          <OrdersTablePanel
            orders={orders}
            onStatusChange={setOrderStatus}
            onViewDetails={(id) => openOrderDetail(id)}
            onSendCourier={(id) => openShipping(id)}
          />
        </OrdersPanel.Body>
      </OrdersPanel>

      <ShippingCourierModal
        order={shippingOrder}
        open={Boolean(shippingOrderId)}
        loading={ordersActionLoading}
        onClose={closeShipping}
        onConfirm={() => shippingOrderId && void confirmShipping(shippingOrderId)}
      />

      <OrderDetailModal order={detailOrder} open={Boolean(orderDetailId)} onClose={() => openOrderDetail(null)} />
    </div>
  );
}
