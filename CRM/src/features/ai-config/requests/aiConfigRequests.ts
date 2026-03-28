/**
 * API layer for AI config. Wire to Laravel with `apiRequest` — see `HillSide-BackEnd/docs/API_ENDPOINTS.md`.
 */

import type { AiConfigDraft } from '../types';

/** Placeholder: no network call until the page is wired to a real business id + endpoint. */
export async function getAiConfigForBusiness(_businessId: number): Promise<AiConfigDraft | null> {
  return Promise.resolve(null);
}
