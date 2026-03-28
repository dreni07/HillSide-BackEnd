import type { SocialMessage } from '../../types/ordersAutomation';

const STATUS_LABEL: Record<SocialMessage['status'], string> = {
  pending_review: 'Në pritje',
  approved: 'Aprovuar',
  rejected: 'Refuzuar',
};

function formatTime(iso: string): string {
  try {
    return new Date(iso).toLocaleString('sq-AL', {
      day: '2-digit',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return iso;
  }
}

interface MessagesListPanelProps {
  messages: SocialMessage[];
  selectedId: string | null;
  onSelect: (id: string) => void;
}

export function MessagesListPanel({ messages, selectedId, onSelect }: MessagesListPanelProps) {
  return (
    <ul className="orders-message-list">
      {messages.map((m) => (
        <li key={m.id}>
          <button
            type="button"
            className={`orders-message-item${selectedId === m.id ? ' orders-message-item--active' : ''}`}
            onClick={() => onSelect(m.id)}
          >
            <span className="orders-message-preview">{m.text}</span>
            <span className="orders-message-meta">
              <span className="orders-message-time">{formatTime(m.timestamp)}</span>
              <span className={`orders-badge orders-badge--${m.status}`}>{STATUS_LABEL[m.status]}</span>
            </span>
            <span className="orders-message-source">{m.source}</span>
          </button>
        </li>
      ))}
    </ul>
  );
}
