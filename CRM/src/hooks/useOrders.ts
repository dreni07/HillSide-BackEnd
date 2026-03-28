import { useOrdersAutomationContext } from '../context/OrdersAutomationContext';

/**
 * Porositë e aprovuara dhe dërgesa (mock).
 */
export function useOrders() {
  const {
    state,
    setOrderStatus,
    openShipping,
    closeShipping,
    confirmShipping,
    openOrderDetail,
    clearToast,
  } = useOrdersAutomationContext();

  return {
    orders: state.orders,
    shippingOrderId: state.shippingOrderId,
    orderDetailId: state.orderDetailId,
    actionLoading: state.actionLoading,
    toast: state.toast,
    setOrderStatus,
    openShipping,
    closeShipping,
    confirmShipping,
    openOrderDetail,
    clearToast,
  };
}
