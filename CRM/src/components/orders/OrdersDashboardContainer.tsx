import { OrdersAutomationProvider } from '../../context/OrdersAutomationContext';
import { OrdersDashboardView } from './OrdersDashboardView';

/** Container: ofron state global (Context + reducer) për pamjen e dashboard-it. */
export function OrdersDashboardContainer() {
  return (
    <OrdersAutomationProvider>
      <OrdersDashboardView />
    </OrdersAutomationProvider>
  );
}
