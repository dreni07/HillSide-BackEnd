import { useEffect, useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { apiRequest, getStoredToken, syncChannelConnection } from '../services/api';
import { env } from '../config/env';
import type { Channel, ChannelPlatform, ChannelStatus } from '../types/channel';
import { CHANNEL_PLATFORM_LABELS } from '../types/channel';

export function ChannelDetail() {
  const { channelId } = useParams<{ channelId: string }>();
  const navigate = useNavigate();
  const [channel, setChannel] = useState<Channel | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [name, setName] = useState('');
  const [status, setStatus] = useState<ChannelStatus>('active');
  const [aiInstructions, setAiInstructions] = useState('');
  const [saving, setSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [reconnectToken, setReconnectToken] = useState('');
  const [syncing, setSyncing] = useState(false);
  const [syncWarnings, setSyncWarnings] = useState<string[]>([]);

  useEffect(() => {
    if (!channelId) return;
    setLoading(true);
    setError('');
    apiRequest<Channel>(`/api/channels/${channelId}`)
      .then((data) => {
        setChannel(data);
        setName(data.name ?? '');
        setStatus(data.status as ChannelStatus);
        setAiInstructions(data.aiInstructions ?? '');
      })
      .catch((err) => setError(err instanceof Error ? err.message : 'Kanali nuk u gjet.'))
      .finally(() => setLoading(false));
  }, [channelId]);

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!channelId) return;
    setSaving(true);
    setSaveSuccess(false);
    apiRequest<Channel>(`/api/channels/${channelId}`, {
      method: 'PUT',
      body: JSON.stringify({ name: name.trim() || null, status, aiInstructions: aiInstructions.trim() }),
    })
      .then((data) => {
        setChannel(data);
        setSaveSuccess(true);
        setTimeout(() => setSaveSuccess(false), 3000);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Gabim gjatë ruajtjes.');
      })
      .finally(() => setSaving(false));
  }

  function handleDelete() {
    if (!channelId || !window.confirm('Jeni të sigurt që dëshironi ta fshini këtë kanal?')) return;
    setDeleting(true);
    apiRequest(`/api/channels/${channelId}`, { method: 'DELETE' })
      .then(() => navigate('/app/channels', { replace: true }))
      .catch((err) => setError(err instanceof Error ? err.message : 'Gabim gjatë fshirjes.'))
      .finally(() => setDeleting(false));
  }

  if (loading) return <div className="page-loading">Duke ngarkuar…</div>;
  if (error && !channel) return <div className="page-error" role="alert">{error}</div>;
  if (!channel) return null;

  const canManageCredentials = channel.canManageCredentials !== false;
  const tokenBlocked =
    channel.tokenStatus === 'needs_reconnect' ||
    channel.tokenStatus === 'expired' ||
    channel.tokenStatus === 'invalid';
  const tokenExpiringSoon = channel.tokenStatus === 'expiring_soon';
  const isMetaFamily = ['facebook', 'instagram', 'whatsapp'].includes(channel.platform);

  function startMetaOAuth() {
    const token = getStoredToken();
    if (!token) {
      return;
    }
    window.location.href = `${env.apiUrl}/api/oauth/meta/start?token=${encodeURIComponent(token)}`;
  }

  async function handleRefreshConnection() {
    if (!channelId) return;
    setSyncing(true);
    setSyncWarnings([]);
    setError('');
    try {
      const trimmed = reconnectToken.trim();
      if (trimmed) {
        await apiRequest<Channel>(`/api/channels/${channelId}`, {
          method: 'PUT',
          body: JSON.stringify({ accessToken: trimmed }),
        });
      }
      const { channel: next, warnings } = await syncChannelConnection(channelId);
      setChannel(next);
      setReconnectToken('');
      if (warnings.length > 0) {
        setSyncWarnings(warnings);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Sinkronizimi dështoi.');
    } finally {
      setSyncing(false);
    }
  }

  function formatIso(iso: string | null | undefined): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? iso : d.toLocaleString();
  }

  return (
    <div className="page-channel-detail">
      <div className="channel-detail-header">
        <Link to="/app/channels" className="back-link">← Kanale</Link>
        <h1>
          {CHANNEL_PLATFORM_LABELS[channel.platform as ChannelPlatform]}
          {channel.name ? `: ${channel.name}` : ''}
        </h1>
      </div>
      <div className="channel-chatbot-toggle">
        <span className="channel-chatbot-label">Chatbot aktiv</span>
        <label className="chatbot-switch-label" onClick={(e) => e.preventDefault()}>
          <input
            type="checkbox"
            className="chatbot-switch"
            checked={status === 'active'}
            readOnly
            onClick={(e) => {
              e.preventDefault();
              if (tokenBlocked) {
                setError(
                  'Ky kanal ka nevojë për rilidhje (token-i ka skaduar ose është i pavlefshëm). Përdorni panelin “Lidhja me platformën” më poshtë.'
                );
                return;
              }
              const next = status === 'active' ? 'inactive' : 'active';
              setStatus(next);
              if (!channelId) return;
              apiRequest<Channel>(`/api/channels/${channelId}`, {
                method: 'PUT',
                body: JSON.stringify({ status: next }),
              })
                .then((data) => setChannel(data))
                .catch(() => setError('Gabim gjatë ndryshimit.'));
            }}
          />
          <span className="chatbot-switch-slider" />
        </label>
        <span className="channel-chatbot-hint">{status === 'active' ? 'ON – përgjigje automatike' : 'OFF – vetëm përgjigje manuale'}</span>
      </div>
      {tokenExpiringSoon && (
        <div className="form-success" role="status">
          Tokeni Meta skadon së shpejti. Rifreskoni lidhjen ose përditësoni token-in për të shmangur ndërprerjen.
        </div>
      )}
      {tokenBlocked && (
        <div className="auth-error">
          Lidhja me platformën duket e pavlefshëm ose e skaduar. Chatbot-i dhe dërgimi i mesazheve mund të mos funksionojnë derisa të
          rifreskoni token-in ose të rilidhni.
          {isMetaFamily && canManageCredentials && (
            <div style={{ marginTop: '0.5rem' }}>
              <button type="button" className="btn-primary" onClick={startMetaOAuth}>
                Rilidhu përmes Meta OAuth
              </button>
            </div>
          )}
          {!canManageCredentials && (
            <p className="channel-muted" style={{ marginTop: '0.5rem' }}>
              Rilidhja me Meta / token kërkon hyrjen si pronari i kanalit (jo si admin i klientit).
            </p>
          )}
        </div>
      )}
      <section className="channel-connection-panel" aria-labelledby="conn-heading">
        <h2 id="conn-heading" className="channel-section-title">
          Lidhja me platformën
        </h2>
        <dl className="channel-connection-dl">
          <div>
            <dt>Gjendja e lidhjes</dt>
            <dd>
              <span className={`channel-token-status channel-token-status--${channel.tokenStatus ?? 'unknown'}`}>
                {channel.tokenStatus ?? 'unknown'}
              </span>
            </dd>
          </div>
          {channel.platform === 'whatsapp' && (
            <>
              <div>
                <dt>WABA ID</dt>
                <dd>{channel.whatsappBusinessAccountId ?? '—'}</dd>
              </div>
              <div>
                <dt>Phone Number ID</dt>
                <dd>{channel.whatsappPhoneNumberId ?? '—'}</dd>
              </div>
              <div>
                <dt>Telefon (shfaqje)</dt>
                <dd>{channel.whatsappDisplayPhoneNumber ?? '—'}</dd>
              </div>
            </>
          )}
          {isMetaFamily && (
            <div>
              <dt>Skadimi i token-it (Meta)</dt>
              <dd>{formatIso(channel.metaTokenExpiresAt)}</dd>
            </div>
          )}
          {channel.platform === 'viber' && (
            <>
              <div>
                <dt>Webhook i regjistruar</dt>
                <dd>{formatIso(channel.viberWebhookRegisteredAt)}</dd>
              </div>
              <div>
                <dt>URL webhook (Viber PA)</dt>
                <dd>
                  <code className="channel-code-inline">{`${env.apiUrl}/api/webhooks/viber`}</code>
                </dd>
              </div>
            </>
          )}
          {(channel.connectionError || channel.connectionErrorAt) && (
            <div>
              <dt>Gabim i fundit</dt>
              <dd>
                {channel.connectionError ?? '—'}
                {channel.connectionErrorCode ? (
                  <span className="channel-muted"> [{channel.connectionErrorCode}]</span>
                ) : null}
                {channel.connectionErrorAt ? (
                  <span className="channel-muted"> ({formatIso(channel.connectionErrorAt)})</span>
                ) : null}
              </dd>
            </div>
          )}
        </dl>
        {canManageCredentials ? (
          <>
            <label className="channel-reconnect-token-label">
              Token i ri (opsional — Meta ose Viber PA)
              <input
                type="password"
                value={reconnectToken}
                onChange={(e) => setReconnectToken(e.target.value)}
                placeholder="Lëreni bosh për të rifreskuar me token-in e ruajtur"
                autoComplete="off"
              />
            </label>
            <div className="channel-connection-actions">
              <button type="button" className="btn-primary" disabled={syncing} onClick={() => void handleRefreshConnection()}>
                {syncing ? 'Duke rifreskuar…' : 'Rifresko lidhjen'}
              </button>
            </div>
            {syncWarnings.length > 0 && (
              <ul className="channel-sync-warnings" role="list">
                {syncWarnings.map((w) => (
                  <li key={w}>{w}</li>
                ))}
              </ul>
            )}
          </>
        ) : (
          <p className="channel-muted">
            Rifreskimi i lidhjes dhe futja e token-it të ri janë të kufizuara për pronarin e kanalit.
          </p>
        )}
      </section>
      <form onSubmit={handleSubmit} className="channel-detail-form">
        {error && <div className="auth-error">{error}</div>}
        {saveSuccess && <div className="form-success">U ruajt me sukses.</div>}
        <label>
          Emër kanali
          <input
            type="text"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Emër i lexueshëm për këtë kanal"
          />
        </label>
        <label>
          Status (Aktiv = Chatbot ON, Jo aktiv = Chatbot OFF)
          <select value={status} onChange={(e) => setStatus(e.target.value as ChannelStatus)}>
            <option value="active">Aktiv (Chatbot ON)</option>
            <option value="inactive">Jo aktiv (Chatbot OFF)</option>
            <option value="pending">Në pritje</option>
          </select>
        </label>
        <label>
          Udhëzime për AI
          <textarea
            value={aiInstructions}
            onChange={(e) => setAiInstructions(e.target.value)}
            placeholder="Përshkrimi i kompanisë, toni i përgjigjeve, FAQ – përdoret nga chatbot për këtë kanal"
            rows={6}
          />
        </label>
        <div className="form-actions">
          <button type="submit" className="btn-primary" disabled={saving}>
            {saving ? 'Duke ruajtur…' : 'Ruaj ndryshimet'}
          </button>
          <button
            type="button"
            className="btn-danger"
            onClick={handleDelete}
            disabled={deleting}
          >
            {deleting ? 'Duke fshirë…' : 'Fshi kanalin'}
          </button>
        </div>
      </form>
    </div>
  );
}
