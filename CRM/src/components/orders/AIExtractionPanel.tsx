import type { AIExtraction, SocialMessage } from '../../types/ordersAutomation';

interface AIExtractionPanelProps {
  message: SocialMessage;
  isEditing: boolean;
  actionLoading: boolean;
  onPatch: (patch: Partial<AIExtraction>) => void;
  onToggleEdit: () => void;
  onApprove: () => void;
  onReject: () => void;
}

export function AIExtractionPanel({
  message,
  isEditing,
  actionLoading,
  onPatch,
  onToggleEdit,
  onApprove,
  onReject,
}: AIExtractionPanelProps) {
  const { extraction: e } = message;
  const confidencePct = Math.round(e.confidence * 100);
  const canAct = message.status === 'pending_review';
  const canApprove = canAct && e.isOrder;

  return (
    <div className="orders-ai-panel">
      <h3 className="orders-ai-heading">Parapamje nga AI</h3>
      <dl className="orders-dl">
        <div className="orders-dl-row">
          <dt>Emri i plotë</dt>
          <dd>
            {isEditing ? (
              <input
                type="text"
                className="orders-input-inline"
                value={e.fullName}
                onChange={(ev) => onPatch({ fullName: ev.target.value })}
              />
            ) : (
              e.fullName || '—'
            )}
          </dd>
        </div>
        <div className="orders-dl-row">
          <dt>Telefon</dt>
          <dd>
            {isEditing ? (
              <input
                type="text"
                className="orders-input-inline"
                value={e.phone}
                onChange={(ev) => onPatch({ phone: ev.target.value })}
              />
            ) : (
              e.phone || '—'
            )}
          </dd>
        </div>
        <div className="orders-dl-row">
          <dt>Adresa</dt>
          <dd>
            {isEditing ? (
              <input
                type="text"
                className="orders-input-inline"
                value={e.address}
                onChange={(ev) => onPatch({ address: ev.target.value })}
              />
            ) : (
              e.address || '—'
            )}
          </dd>
        </div>
        <div className="orders-dl-row">
          <dt>Produkti</dt>
          <dd>
            {isEditing ? (
              <input
                type="text"
                className="orders-input-inline"
                value={e.product}
                onChange={(ev) => onPatch({ product: ev.target.value })}
              />
            ) : (
              e.product || '—'
            )}
          </dd>
        </div>
        <div className="orders-dl-row">
          <dt>Është porosi</dt>
          <dd>
            {isEditing ? (
              <label className="checkbox-label">
                <input
                  type="checkbox"
                  checked={e.isOrder}
                  onChange={(ev) => onPatch({ isOrder: ev.target.checked })}
                />
                {e.isOrder ? 'Po' : 'Jo'}
              </label>
            ) : (
              <span className={e.isOrder ? 'orders-flag-yes' : 'orders-flag-no'}>{e.isOrder ? 'Po' : 'Jo'}</span>
            )}
          </dd>
        </div>
        <div className="orders-dl-row">
          <dt>Besueshmëria</dt>
          <dd>
            <span className="orders-confidence">{confidencePct}%</span>
          </dd>
        </div>
      </dl>

      <div className="orders-ai-actions">
        <button
          type="button"
          className="btn-primary"
          disabled={!canApprove || actionLoading}
          title={!e.isOrder ? 'AI nuk e klasifikon si porosi — aktivizo “Është porosi” ose përditëso fushat.' : undefined}
          onClick={onApprove}
        >
          {actionLoading ? 'Duke procesuar…' : 'Aprovo porosinë'}
        </button>
        <button type="button" className="btn-danger" disabled={!canAct || actionLoading} onClick={onReject}>
          Refuzo
        </button>
        <button
          type="button"
          className="btn-secondary"
          disabled={!canAct || actionLoading}
          onClick={onToggleEdit}
        >
          {isEditing ? 'Mbyll redaktimin' : 'Ndrysho fushat'}
        </button>
      </div>
    </div>
  );
}
