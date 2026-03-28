import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useReducer,
  type ReactNode,
} from 'react';
import { fetchMockSocialMessages, generateMockTrackingNumber } from '../requests/ordersAutomationRequests';
import type {
  AIExtraction,
  AutomationOrder,
  OrderAutomationStatus,
  OrdersAutomationToast,
  SocialMessage,
} from '../types/ordersAutomation';

interface OrdersAutomationState {
  messages: SocialMessage[];
  selectedMessageId: string | null;
  orders: AutomationOrder[];
  messagesLoading: boolean;
  actionLoading: boolean;
  isEditingExtraction: boolean;
  shippingOrderId: string | null;
  toast: OrdersAutomationToast | null;
  orderDetailId: string | null;
}

const initialState: OrdersAutomationState = {
  messages: [],
  selectedMessageId: null,
  orders: [],
  messagesLoading: true,
  actionLoading: false,
  isEditingExtraction: false,
  shippingOrderId: null,
  toast: null,
  orderDetailId: null,
};

type Action =
  | { type: 'LOAD_MESSAGES_START' }
  | { type: 'LOAD_MESSAGES_SUCCESS'; payload: SocialMessage[] }
  | { type: 'LOAD_MESSAGES_ERROR' }
  | { type: 'SELECT_MESSAGE'; payload: string | null }
  | { type: 'SET_TOAST'; payload: OrdersAutomationToast | null }
  | { type: 'SET_EDITING'; payload: boolean }
  | { type: 'PATCH_EXTRACTION'; payload: { messageId: string; patch: Partial<AIExtraction> } }
  | { type: 'APPROVE_SELECTED' }
  | { type: 'REJECT_SELECTED' }
  | { type: 'SET_ORDER_STATUS'; payload: { orderId: string; status: OrderAutomationStatus } }
  | { type: 'OPEN_SHIPPING'; payload: string }
  | { type: 'CLOSE_SHIPPING' }
  | { type: 'CONFIRM_SHIPPING'; payload: { orderId: string; trackingNumber: string } }
  | { type: 'SET_ACTION_LOADING'; payload: boolean }
  | { type: 'OPEN_ORDER_DETAIL'; payload: string | null }
  | { type: 'CLEAR_TOAST' };

function reducer(state: OrdersAutomationState, action: Action): OrdersAutomationState {
  switch (action.type) {
    case 'LOAD_MESSAGES_START':
      return { ...state, messagesLoading: true };
    case 'LOAD_MESSAGES_SUCCESS': {
      const onlyOrders = action.payload.filter((m) => m.extraction.isOrder);
      return {
        ...state,
        messagesLoading: false,
        messages: onlyOrders,
        selectedMessageId: onlyOrders[0]?.id ?? null,
      };
    }
    case 'LOAD_MESSAGES_ERROR':
      return { ...state, messagesLoading: false, toast: { type: 'error', text: 'Gabim në ngarkimin e mesazheve (simulim).' } };
    case 'SELECT_MESSAGE':
      return { ...state, selectedMessageId: action.payload, isEditingExtraction: false };
    case 'SET_TOAST':
      return { ...state, toast: action.payload };
    case 'SET_EDITING':
      return { ...state, isEditingExtraction: action.payload };
    case 'PATCH_EXTRACTION': {
      const { messageId, patch } = action.payload;
      const nextMessages = state.messages.map((m) =>
        m.id === messageId ? { ...m, extraction: { ...m.extraction, ...patch } } : m
      );
      const updated = nextMessages.find((m) => m.id === messageId);
      if (updated && !updated.extraction.isOrder) {
        const filtered = nextMessages.filter((m) => m.extraction.isOrder);
        const newSelected =
          state.selectedMessageId === messageId ? filtered[0]?.id ?? null : state.selectedMessageId;
        return {
          ...state,
          messages: filtered,
          selectedMessageId: newSelected,
          isEditingExtraction: false,
        };
      }
      return { ...state, messages: nextMessages };
    }
    case 'APPROVE_SELECTED': {
      const id = state.selectedMessageId;
      if (!id) return state;
      if (state.orders.some((o) => o.messageId === id)) {
        return { ...state, toast: { type: 'error', text: 'Kjo porosi është procesuar tashmë.' } };
      }
      const msg = state.messages.find((m) => m.id === id);
      if (!msg || msg.status !== 'pending_review') return state;
      const orderId = `ord-${Date.now()}`;
      const newOrder: AutomationOrder = {
        id: orderId,
        messageId: msg.id,
        customerName: msg.extraction.fullName || '—',
        phone: msg.extraction.phone || '—',
        address: msg.extraction.address || '—',
        product: msg.extraction.product || '—',
        status: 'pending',
        createdAt: new Date().toISOString(),
      };
      return {
        ...state,
        messages: state.messages.map((m) => (m.id === id ? { ...m, status: 'approved' as const } : m)),
        orders: [newOrder, ...state.orders],
        isEditingExtraction: false,
        toast: { type: 'success', text: 'Porosia u aprovua dhe u shtua në listë (simulim).' },
      };
    }
    case 'REJECT_SELECTED': {
      const id = state.selectedMessageId;
      if (!id) return state;
      return {
        ...state,
        messages: state.messages.map((m) => (m.id === id ? { ...m, status: 'rejected' as const } : m)),
        isEditingExtraction: false,
        toast: { type: 'success', text: 'Mesazhi u refuzua (simulim).' },
      };
    }
    case 'SET_ORDER_STATUS':
      return {
        ...state,
        orders: state.orders.map((o) =>
          o.id === action.payload.orderId ? { ...o, status: action.payload.status } : o
        ),
      };
    case 'OPEN_SHIPPING':
      return { ...state, shippingOrderId: action.payload };
    case 'CLOSE_SHIPPING':
      return { ...state, shippingOrderId: null };
    case 'CONFIRM_SHIPPING':
      return {
        ...state,
        shippingOrderId: null,
        orders: state.orders.map((o) =>
          o.id === action.payload.orderId
            ? { ...o, status: 'shipped' as const, trackingNumber: action.payload.trackingNumber }
            : o
        ),
        toast: { type: 'success', text: `Dërgesa u regjistrua. Numri i gjurmimit: ${action.payload.trackingNumber}` },
      };
    case 'SET_ACTION_LOADING':
      return { ...state, actionLoading: action.payload };
    case 'OPEN_ORDER_DETAIL':
      return { ...state, orderDetailId: action.payload };
    case 'CLEAR_TOAST':
      return { ...state, toast: null };
    default:
      return state;
  }
}

interface OrdersAutomationContextValue {
  state: OrdersAutomationState;
  dispatch: React.Dispatch<Action>;
  selectedMessage: SocialMessage | undefined;
  loadMessages: () => Promise<void>;
  selectMessage: (id: string | null) => void;
  patchExtraction: (messageId: string, patch: Partial<AIExtraction>) => void;
  setEditing: (v: boolean) => void;
  approveOrder: () => Promise<void>;
  rejectMessage: () => Promise<void>;
  setOrderStatus: (orderId: string, status: OrderAutomationStatus) => void;
  openShipping: (orderId: string) => void;
  closeShipping: () => void;
  confirmShipping: (orderId: string) => Promise<void>;
  openOrderDetail: (orderId: string | null) => void;
  clearToast: () => void;
}

const OrdersAutomationContext = createContext<OrdersAutomationContextValue | null>(null);

function useOrdersAutomationContext(): OrdersAutomationContextValue {
  const ctx = useContext(OrdersAutomationContext);
  if (!ctx) {
    throw new Error('OrdersAutomation hooks must be used within OrdersAutomationProvider');
  }
  return ctx;
}

export function OrdersAutomationProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(reducer, initialState);

  const loadMessages = useCallback(async () => {
    dispatch({ type: 'LOAD_MESSAGES_START' });
    try {
      const data = await fetchMockSocialMessages();
      dispatch({ type: 'LOAD_MESSAGES_SUCCESS', payload: data });
    } catch {
      dispatch({ type: 'LOAD_MESSAGES_ERROR' });
    }
  }, []);

  useEffect(() => {
    void loadMessages();
  }, [loadMessages]);

  const selectedMessage = useMemo(
    () => state.messages.find((m) => m.id === state.selectedMessageId),
    [state.messages, state.selectedMessageId]
  );

  const selectMessage = useCallback((id: string | null) => {
    dispatch({ type: 'SELECT_MESSAGE', payload: id });
  }, []);

  const patchExtraction = useCallback((messageId: string, patch: Partial<AIExtraction>) => {
    dispatch({ type: 'PATCH_EXTRACTION', payload: { messageId, patch } });
  }, []);

  const setEditing = useCallback((v: boolean) => {
    dispatch({ type: 'SET_EDITING', payload: v });
  }, []);

  const approveOrder = useCallback(async () => {
    dispatch({ type: 'SET_ACTION_LOADING', payload: true });
    await new Promise((r) => setTimeout(r, 400));
    dispatch({ type: 'APPROVE_SELECTED' });
    dispatch({ type: 'SET_ACTION_LOADING', payload: false });
  }, []);

  const rejectMessage = useCallback(async () => {
    dispatch({ type: 'SET_ACTION_LOADING', payload: true });
    await new Promise((r) => setTimeout(r, 350));
    dispatch({ type: 'REJECT_SELECTED' });
    dispatch({ type: 'SET_ACTION_LOADING', payload: false });
  }, []);

  const setOrderStatus = useCallback((orderId: string, status: OrderAutomationStatus) => {
    dispatch({ type: 'SET_ORDER_STATUS', payload: { orderId, status } });
  }, []);

  const openShipping = useCallback((orderId: string) => {
    dispatch({ type: 'OPEN_SHIPPING', payload: orderId });
  }, []);

  const closeShipping = useCallback(() => {
    dispatch({ type: 'CLOSE_SHIPPING' });
  }, []);

  const confirmShipping = useCallback(async (orderId: string) => {
    dispatch({ type: 'SET_ACTION_LOADING', payload: true });
    await new Promise((r) => setTimeout(r, 600));
    const trackingNumber = generateMockTrackingNumber();
    dispatch({ type: 'CONFIRM_SHIPPING', payload: { orderId, trackingNumber } });
    dispatch({ type: 'SET_ACTION_LOADING', payload: false });
  }, []);

  const openOrderDetail = useCallback((orderId: string | null) => {
    dispatch({ type: 'OPEN_ORDER_DETAIL', payload: orderId });
  }, []);

  const clearToast = useCallback(() => {
    dispatch({ type: 'CLEAR_TOAST' });
  }, []);

  const value = useMemo<OrdersAutomationContextValue>(
    () => ({
      state,
      dispatch,
      selectedMessage,
      loadMessages,
      selectMessage,
      patchExtraction,
      setEditing,
      approveOrder,
      rejectMessage,
      setOrderStatus,
      openShipping,
      closeShipping,
      confirmShipping,
      openOrderDetail,
      clearToast,
    }),
    [
      state,
      selectedMessage,
      loadMessages,
      selectMessage,
      patchExtraction,
      setEditing,
      approveOrder,
      rejectMessage,
      setOrderStatus,
      openShipping,
      closeShipping,
      confirmShipping,
      openOrderDetail,
      clearToast,
    ]
  );

  return <OrdersAutomationContext.Provider value={value}>{children}</OrdersAutomationContext.Provider>;
}

export { useOrdersAutomationContext };
