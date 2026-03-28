/**
 * Thirrje të përbashkëta për ngarkim produktesh (Laravel + JWT).
 */

import { apiPostFormData, apiRequest } from '../../../services/api';
import type { ProductUploadItemsData } from '../types/apiProductUpload';
import type { ManualProductPayload, ProductUploadApiResult } from '../types/productUpload.types';

function productUploadPath(businessId: string, segment: 'document' | 'image' | 'manual'): string {
  return `/api/businesses/${businessId}/product-uploads/${segment}`;
}

/**
 * Ngarkon një skedar dokumenti (PDF, CSV, XLSX, ODS).
 */
export async function uploadProductDocumentFile(
  businessId: string,
  file: File,
): Promise<ProductUploadItemsData> {
  const form = new FormData();
  form.append('file', file);
  return apiPostFormData<ProductUploadItemsData>(productUploadPath(businessId, 'document'), form);
}

/**
 * Ngarkon një imazh për OCR + strukturim AI.
 */
export async function uploadProductImageFile(
  businessId: string,
  file: File,
): Promise<ProductUploadItemsData> {
  const form = new FormData();
  form.append('file', file);
  return apiPostFormData<ProductUploadItemsData>(productUploadPath(businessId, 'image'), form);
}

/**
 * Dërgon produkt të plotësuar manualisht (JSON).
 */
export async function submitManualProductRequest(
  businessId: string,
  payload: ManualProductPayload,
): Promise<ProductUploadItemsData> {
  const body = {
    name: payload.name,
    description: payload.description || null,
    price: payload.price || null,
    sku: payload.sku || null,
    category: payload.category || null,
    stock: payload.stock || null,
    unit: payload.unit || null,
    tags: payload.tags || null,
  };

  return apiRequest<ProductUploadItemsData>(productUploadPath(businessId, 'manual'), {
    method: 'POST',
    body: JSON.stringify(body),
  });
}

/**
 * Ruajtje manuale përmes API — kthen rezultat të unifikuar për kontekstin.
 */
export async function submitManualProduct(
  payload: ManualProductPayload,
  options?: { businessId?: string | null },
): Promise<ProductUploadApiResult<{ itemsCount: number }>> {
  if (!payload.name.trim()) {
    return { ok: false, error: 'Emri mungon.', code: 'VALIDATION' };
  }

  const businessId = options?.businessId?.trim();
  if (!businessId) {
    return {
      ok: false,
      error: 'Mungon biznesi. Hyni përsëri ose plotësoni profilin e biznesit.',
      code: 'NO_BUSINESS',
    };
  }

  try {
    const data = await submitManualProductRequest(businessId, payload);
    return { ok: true, data: { itemsCount: data.items?.length ?? 0 } };
  } catch (e) {
    const message = e instanceof Error ? e.message : 'Ruajtja dështoi.';
    return { ok: false, error: message, code: 'NETWORK_OR_SERVER' };
  }
}
