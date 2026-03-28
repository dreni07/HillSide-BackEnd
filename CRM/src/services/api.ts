/**
 * Klienti API – URL bazë dhe dërgimi i JWT në header për rrugët e mbrojtura.
 * Implementuar me `axios` për thirrjet e autentifikuara dhe `fetch` aty ku ka kuptim.
 */

import axios, { AxiosError, type AxiosRequestConfig } from 'axios';
import { env } from '../config/env';
import type { StoredUser, ApiSuccess, ApiError, ApiResponse, AuthResponse } from '../types/api';

export type {
  StoredUser,
  ApiSuccess,
  ApiError,
  ApiResponse,
  BusinessType,
  Business,
  BusinessTypesResponse,
  AuthResponse,
} from '../types/api';

const STORAGE_KEY_TOKEN = 'crm_token';
const STORAGE_KEY_USER = 'crm_user';

export function getStoredToken(): string | null {
  return localStorage.getItem(STORAGE_KEY_TOKEN);
}

/** Përshtat përgjigjen e Laravel (`is_admin`, etj.) me formën e CRM-së. */
export function normalizeAuthUser(raw: Record<string, unknown>): StoredUser {
  const isAdmin = Boolean(raw.is_admin);
  const oc = raw.onboarding_completed;
  const completed = oc === true || oc === 1 || oc === '1';
  return {
    id: String(raw.id ?? ''),
    name: String(raw.name ?? ''),
    email: String(raw.email ?? ''),
    role: isAdmin ? 'admin' : 'client',
    onboarding_completed: completed,
  };
}

export function getStoredUser(): StoredUser | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_USER);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Record<string, unknown>;
    return normalizeAuthUser(parsed);
  } catch {
    return null;
  }
}

export function setStoredAuth(token: string, user: StoredUser): void {
  localStorage.setItem(STORAGE_KEY_TOKEN, token);
  localStorage.setItem(STORAGE_KEY_USER, JSON.stringify(user));
}

export function clearStoredAuth(): void {
  localStorage.removeItem(STORAGE_KEY_TOKEN);
  localStorage.removeItem(STORAGE_KEY_USER);
}

export const apiClient = axios.create({
  baseURL: env.apiUrl,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

apiClient.interceptors.request.use((config) => {
  const token = getStoredToken();
  if (token) {
    config.headers = config.headers ?? {};
    (config.headers as Record<string, string>)['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

function handleUnauthorized(): never {
  clearStoredAuth();
  window.location.replace('/login');
  throw new Error('Session e skaduar. Ju ridrejtoheni te faqja e hyrjes.');
}

/** Përdoret edhe nga modulët që nuk kalojnë nëpër `apiRequest` (p.sh. FormData). */
export function extractValidationMessage(data: unknown): string | null {
  if (!data || typeof data !== 'object') return null;
  const anyData = data as { message?: string; errors?: Record<string, string[] | string> };
  if (anyData.errors && typeof anyData.errors === 'object') {
    const firstKey = Object.keys(anyData.errors)[0];
    const firstVal = firstKey ? anyData.errors[firstKey] : undefined;
    if (Array.isArray(firstVal) && firstVal.length > 0) return firstVal[0];
    if (typeof firstVal === 'string') return firstVal;
  }
  if (typeof anyData.message === 'string') return anyData.message;
  return null;
}

/** Heq HTML nga mesazhet e gabimit (kur PHP shton Notice para JSON). */
export function sanitizeApiUserMessage(raw: string): string {
  return raw
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\r\n/g, '\n')
    .replace(/[ \t]+\n/g, '\n')
    .replace(/\n{3,}/g, '\n\n')
    .replace(/[ \t]{2,}/g, ' ')
    .trim();
}

/** Parse JSON; nëse përpara përgjigjes ka HTML (Notice), përpiqet të gjejë objektin `{"success":...}`. */
function parseJsonBodyLenient(rawText: string): { ok: true; value: unknown } | { ok: false } {
  const t = rawText.trim();
  if (!t) return { ok: true, value: {} };
  try {
    return { ok: true, value: JSON.parse(t) };
  } catch {
    const idx = t.indexOf('{"success"');
    if (idx !== -1) {
      try {
        return { ok: true, value: JSON.parse(t.slice(idx)) };
      } catch {
        /* ignore */
      }
    }
  }
  return { ok: false };
}

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {}
): Promise<ApiSuccess<T>['data']> {
  const url = path.startsWith('http') ? path : `${env.apiUrl}${path}`;
  let data: unknown;
  if (options.body !== undefined) {
    if (typeof options.body === 'string') {
      try {
        data = JSON.parse(options.body);
      } catch {
        data = options.body;
      }
    } else {
      data = options.body;
    }
  }

  const config: AxiosRequestConfig = {
    url,
    method: (options.method ?? 'GET') as AxiosRequestConfig['method'],
    data,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers as Record<string, string> | undefined),
    },
  };

  try {
    const res = await apiClient.request<ApiResponse<T> | unknown>(config);
    const body = res.data;

    if (res.status === 401) {
      handleUnauthorized();
    }

    if (
      res.status >= 200 &&
      res.status < 300 &&
      typeof body === 'object' &&
      body !== null &&
      'success' in body &&
      (body as ApiResponse<T>).success === true
    ) {
      const success = body as ApiSuccess<T>;
      return success.data;
    }

    const errMsg =
      (typeof body === 'object' &&
        body !== null &&
        'message' in body &&
        typeof (body as ApiError).message === 'string' &&
        (body as ApiError).message) ||
      (res.status === 403
        ? 'Nuk keni qasje.'
        : res.status === 404
          ? 'Nuk u gjet.'
          : res.status >= 500
            ? 'Gabim në server. Provoni përsëri më vonë.'
            : (res.statusText || 'Gabim në server.'));

    throw new Error(errMsg);
  } catch (error) {
    if (axios.isAxiosError(error) && error.response) {
      if (error.response.status === 401) {
        handleUnauthorized();
      }
      if (error.response.status === 422) {
        const msg = extractValidationMessage(error.response.data) ?? 'Të dhënat e futura nuk janë të vlefshme.';
        throw new Error(msg);
      }
      const msg =
        extractValidationMessage(error.response.data) ??
        (error.response.statusText || 'Gabim në server. Provoni përsëri më vonë.');
      throw new Error(msg);
    }
    throw new Error('Gabim në komunikim me serverin. Kontrolloni lidhjen dhe provoni përsëri.');
  }
}

/**
 * POST multipart/form-data me JWT (pa Content-Type manual — boundary nga shfletuesi).
 * Përgjigjja e pritur: `{ success: true, data: T }` si rrugët e tjera të API-së.
 */
export async function apiPostFormData<T>(path: string, formData: FormData): Promise<T> {
  const token = getStoredToken();
  if (!token) {
    handleUnauthorized();
  }

  const url = path.startsWith('http') ? path : `${env.apiUrl}${path}`;

  let res: Response;
  try {
    res = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    });
  } catch {
    throw new Error('Gabim në komunikim me serverin. Kontrolloni lidhjen dhe provoni përsëri.');
  }

  const rawText = await res.text();
  const parsed = parseJsonBodyLenient(rawText);
  const body: unknown = parsed.ok ? parsed.value : {};

  if (res.status === 401) {
    handleUnauthorized();
  }

  if (
    res.ok &&
    parsed.ok &&
    typeof body === 'object' &&
    body !== null &&
    'success' in body &&
    (body as ApiResponse<T>).success === true
  ) {
    return (body as ApiSuccess<T>).data;
  }

  let errMsg =
    extractValidationMessage(body) ??
    ((typeof body === 'object' &&
      body !== null &&
      'message' in body &&
      typeof (body as ApiError).message === 'string' &&
      (body as ApiError).message) ||
      null);

  if (!errMsg) {
    errMsg =
      res.status === 403
        ? 'Nuk keni qasje.'
        : res.status === 404
          ? 'Nuk u gjet.'
          : res.status === 422
            ? 'Të dhënat e futura nuk janë të vlefshme.'
            : res.status >= 500
              ? 'Gabim në server. Provoni përsëri më vonë.'
              : res.statusText || 'Gabim në server.';
  }

  if (!parsed.ok && rawText.trim()) {
    const cleaned = sanitizeApiUserMessage(rawText);
    if (cleaned.length > 0) {
      errMsg = cleaned.length > 800 ? `${cleaned.slice(0, 800)}…` : cleaned;
    }
  } else if (errMsg) {
    errMsg = sanitizeApiUserMessage(errMsg);
  }

  throw new Error(errMsg);
}

export async function apiAuthRequest<T = AuthResponse['data']>(
  path: string,
  body: unknown,
  method: 'POST' = 'POST'
): Promise<{ data: T; token: string }> {
  const url = path.startsWith('http') ? path : `${env.apiUrl}${path}`;
  try {
    const res = await apiClient.request<AuthResponse | ApiError>({
      url,
      method,
      data: body,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    const raw = res.data;

    if (typeof raw === 'object' && raw !== null && 'success' in raw && (raw as AuthResponse).success === true) {
      const auth = raw as AuthResponse;
      const payload = auth.data as unknown as Record<string, unknown>;
      return { data: normalizeAuthUser(payload) as T, token: auth.token };
    }

    const msg =
      (typeof raw === 'object' &&
        raw !== null &&
        'message' in raw &&
        typeof (raw as ApiError).message === 'string' &&
        (raw as ApiError).message) ||
      'Gabim.';
    throw new Error(msg);
  } catch (error) {
    if (axios.isAxiosError(error)) {
      const err = error as AxiosError<any>;
      const status = err.response?.status;
      const data = err.response?.data;

      if (status === 401) {
        const msg =
          (data && typeof data === 'object' && typeof (data as any).message === 'string' && (data as any).message) ||
          'Kredencialet janë të pasakta.';
        throw new Error(msg);
      }

      if (status === 422) {
        const msg = extractValidationMessage(data) ?? 'Të dhënat e futura nuk janë të vlefshme.';
        throw new Error(msg);
      }

      const msg =
        extractValidationMessage(data) ??
        (err.response?.statusText || 'Gabim në server. Provoni përsëri më vonë.');
      throw new Error(msg);
    }

    throw new Error('Gabim në komunikim me serverin. Kontrolloni lidhjen dhe provoni përsëri.');
  }
}
