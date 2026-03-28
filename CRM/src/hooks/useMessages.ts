import { useMemo } from 'react';
import { useOrdersAutomationContext } from '../context/OrdersAutomationContext';

/**
 * Mesazhet nga rrjetet sociale dhe përzgjedhja (mock / state lokal).
 */
export function useMessages() {
  const {
    state,
    selectedMessage,
    selectMessage,
    patchExtraction,
    setEditing,
    approveOrder,
    rejectMessage,
    loadMessages,
    clearToast,
  } = useOrdersAutomationContext();

  const pendingCount = useMemo(
    () => state.messages.filter((m) => m.status === 'pending_review').length,
    [state.messages]
  );

  return {
    messages: state.messages,
    selectedMessageId: state.selectedMessageId,
    selectedMessage,
    messagesLoading: state.messagesLoading,
    actionLoading: state.actionLoading,
    isEditingExtraction: state.isEditingExtraction,
    toast: state.toast,
    pendingCount,
    selectMessage,
    patchExtraction,
    setEditing,
    approveOrder,
    rejectMessage,
    reload: loadMessages,
    clearToast,
  };
}
