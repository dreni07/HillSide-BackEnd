/**
 * AI config API — authenticated routes under `/api/businesses/{business}`.
 */

import { apiRequest, type Business } from '../../../services/api';
import type { ApiAiConfigSaveData, ApiAiConfigShowData } from '../types/apiAiConfig';
import type { SaveAiConfigPayload } from './buildAiConfigPayload';

export async function fetchAuthenticatedBusiness(): Promise<Business> {
  return apiRequest<Business>('/api/business/me');
}

export async function fetchAiConfigShow(businessId: number | string): Promise<ApiAiConfigShowData> {
  return apiRequest<ApiAiConfigShowData>(`/api/businesses/${businessId}/ai-config`);
}

export async function saveAiConfig(
  businessId: number | string,
  payload: SaveAiConfigPayload,
): Promise<ApiAiConfigSaveData> {
  return apiRequest<ApiAiConfigSaveData>(`/api/businesses/${businessId}/ai-config/save`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}
