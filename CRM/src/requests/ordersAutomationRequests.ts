/**
 * Mock requests – zëvendësohen me thirrje API kur backend-i jetë gati.
 */
import type { SocialMessage } from '../types/ordersAutomation';

/** Vetëm mesazhe që AI i klasifikon si porosi — lista e dashboard-it nuk shfaq pyetje të përgjithshme / small talk. */
const MOCK_MESSAGES: SocialMessage[] = [
  {
    id: 'msg-1',
    text: 'Përshëndetje! Dua 2x krem hidratues dhe dërgesë në Rruga Dëshmorët 15, Prishtinë. Emri: Arben Krasniqi, tel 044-123-456.',
    timestamp: new Date(Date.now() - 1000 * 60 * 45).toISOString(),
    source: 'Instagram DM',
    status: 'pending_review',
    extraction: {
      fullName: 'Arben Krasniqi',
      phone: '044-123-456',
      address: 'Rruga Dëshmorët 15, Prishtinë',
      product: '2x krem hidratues',
      isOrder: true,
      confidence: 0.92,
    },
  },
  {
    id: 'msg-3',
    text: 'Porosit një set kujdesi fytyre për Nora Berisha, tel 049-999-888, adresë: Lagjja e Spitalit, Gjakovë.',
    timestamp: new Date(Date.now() - 1000 * 60 * 200).toISOString(),
    source: 'Instagram DM',
    status: 'pending_review',
    extraction: {
      fullName: 'Nora Berisha',
      phone: '049-999-888',
      address: 'Lagjja e Spitalit, Gjakovë',
      product: 'set kujdesi fytyre',
      isOrder: true,
      confidence: 0.88,
    },
  },
  {
    id: 'msg-5',
    text: 'Dua të porosis 1x serum për fytyrë, dërgesë në Ferizaj. Blendi Hoxha 045-111-222, Rruga Skënderbeu nr. 8.',
    timestamp: new Date(Date.now() - 1000 * 60 * 90).toISOString(),
    source: 'Facebook Messenger',
    status: 'pending_review',
    extraction: {
      fullName: 'Blendi Hoxha',
      phone: '045-111-222',
      address: 'Rruga Skënderbeu nr. 8, Ferizaj',
      product: '1x serum për fytyrë',
      isOrder: true,
      confidence: 0.9,
    },
  },
];

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/** Simulon ngarkimin e mesazheve — kur lidhet me API, përdorni të njëjtin filtër: vetëm kandidatë porosi. */
export async function fetchMockSocialMessages(options?: { delayMs?: number }): Promise<SocialMessage[]> {
  const ms = options?.delayMs ?? 700;
  await delay(ms);
  return MOCK_MESSAGES.filter((m) => m.extraction.isOrder).map((m) => ({
    ...m,
    extraction: { ...m.extraction },
  }));
}

/** Gjeneron numër gjurmimi mock (format i qëndrueshëm për UI) */
export function generateMockTrackingNumber(): string {
  const part = () => Math.random().toString(36).slice(2, 8).toUpperCase();
  return `HS-${part()}-${part().slice(0, 4)}`;
}
