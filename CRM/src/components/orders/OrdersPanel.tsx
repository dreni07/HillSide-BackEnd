import type { ReactNode } from 'react';

/**
 * Compound component – seksion kartë në stilin e settings-section.
 */
function OrdersPanelRoot({ children, className }: { children: ReactNode; className?: string }) {
  return <section className={`settings-section orders-panel ${className ?? ''}`.trim()}>{children}</section>;
}

function Title({ children }: { children: ReactNode }) {
  return <h2 className="orders-panel-title">{children}</h2>;
}

function Body({ children }: { children: ReactNode }) {
  return <div className="orders-panel-body">{children}</div>;
}

export const OrdersPanel = Object.assign(OrdersPanelRoot, { Title, Body });
