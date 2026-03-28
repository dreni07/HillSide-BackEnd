/**
 * Tipet për dashboard-in e automatizimit të porosive (frontend-only, gati për integrim API).
 */

export type OrderAutomationStatus = 'pending' | 'ready' | 'shipped';

export type SocialMessageStatus = 'pending_review' | 'approved' | 'rejected';

export interface AIExtraction {
  fullName: string;
  phone: string;
  address: string;
  product: string;
  isOrder: boolean;
  /** 0–1 */
  confidence: number;
}

export interface SocialMessage {
  id: string;
  text: string;
  /** ISO string */
  timestamp: string;
  /** p.sh. Instagram DM */
  source: string;
  status: SocialMessageStatus;
  extraction: AIExtraction;
}

export interface AutomationOrder {
  id: string;
  messageId: string;
  customerName: string;
  phone: string;
  address: string;
  product: string;
  status: OrderAutomationStatus;
  trackingNumber?: string;
  /** ISO */
  createdAt: string;
}

export interface OrdersAutomationToast {
  type: 'success' | 'error';
  text: string;
}
