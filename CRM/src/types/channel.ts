/**
 * Llojet për Channel – në përputhje me backend (platform enum).
 */

export type ChannelPlatform = 'instagram' | 'facebook' | 'whatsapp' | 'viber';
export type ChannelStatus = 'active' | 'inactive' | 'pending' | 'throttled' | 'suspended';
export type ChannelTokenStatus =
  | 'valid'
  | 'expiring_soon'
  | 'expired'
  | 'unknown'
  | 'needs_reconnect'
  /** Legacy nga përgjigje të vjetra API */
  | 'invalid';

export interface Channel {
  _id: string;
  userId: string;
  platform: ChannelPlatform;
  platformPageId: string | null;
  /** WhatsApp Cloud: Phone number ID (webhook metadata.phone_number_id). */
  whatsappPhoneNumberId: string | null;
  whatsappBusinessAccountId: string | null;
  whatsappDisplayPhoneNumber: string | null;
  viberBotId: string | null;
  accessToken?: string; // backend e fsheh ose e maskon si ***
  /** Token verifikimi ekziston (vlera nuk ekspozohet nga API). */
  hasWebhookVerifyToken?: boolean;
  /** @deprecated Përdorni hasWebhookVerifyToken — vlera e token-it nuk duhet në API. */
  webhookVerifyToken?: string | null;
  /** Kur u regjistrua webhook-i te Viber (ISO). */
  viberWebhookRegisteredAt: string | null;
  /** Skadimi i token-it Meta sipas debug_token (ISO), nëse ekziston. */
  metaTokenExpiresAt: string | null;
  connectionError: string | null;
  connectionErrorCode?: string | null;
  connectionErrorAt: string | null;
  /** Vetëm pronari i kanalit mund të rifreskojë token / OAuth reconnect në UI. */
  canManageCredentials?: boolean;
  status: ChannelStatus;
  tokenStatus?: ChannelTokenStatus;
  name: string | null;
  aiInstructions: string;
  createdAt: string;
  updatedAt: string;
}

export const CHANNEL_PLATFORM_LABELS: Record<ChannelPlatform, string> = {
  instagram: 'Instagram',
  facebook: 'Facebook',
  whatsapp: 'WhatsApp',
  viber: 'Viber',
};

export const CHANNEL_STATUS_LABELS: Record<ChannelStatus, string> = {
  active: 'Aktiv',
  inactive: 'Jo aktiv',
  pending: 'Në pritje',
  throttled: 'I kufizuar (rate limit / risk)',
  suspended: 'I pezulluar',
};
