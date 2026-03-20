export interface StoredUser {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'client';
}

export interface ApiSuccess<T> {
  success: true;
  data: T;
  token?: string;
}

export interface ApiError {
  success: false;
  message: string;
}

export type ApiResponse<T> = ApiSuccess<T> | ApiError;

export interface BusinessType {
  id: number;
  category: 'commerce' | 'services';
  name: string;
  slug: string;
  description: string | null;
  icon: string | null;
  is_active: boolean;
}

export interface Business {
  id: number;
  user_id: number;
  business_type_id: number | null;
  name: string;
  description: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  logo: string | null;
  website: string | null;
  timezone: string | null;
  created_at: string;
  updated_at: string;
  business_type: BusinessType | null;
}

export interface BusinessTypesResponse {
  data: BusinessType[];
  grouped: Record<string, BusinessType[]>;
}

export interface AuthResponse {
  success: true;
  data: StoredUser;
  token: string;
}
