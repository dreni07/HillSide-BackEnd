import type { SocialMessage } from '../../types/ordersAutomation';

interface MessageDetailBlockProps {
  message: SocialMessage;
}

function formatTime(iso: string): string {
  try {
    return new Date(iso).toLocaleString('sq-AL', {
      weekday: 'short',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return iso;
  }
}

export function MessageDetailBlock({ message }: MessageDetailBlockProps) {
  return (
    <div className="orders-message-detail">
      <p className="orders-message-detail-text">{message.text}</p>
      <p className="orders-message-detail-meta">
        {formatTime(message.timestamp)} · {message.source}
      </p>
    </div>
  );
}
