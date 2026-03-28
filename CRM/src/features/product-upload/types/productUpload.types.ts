/**
 * Types për modulin e ngarkimit të produkteve (UI, API, kontekst).
 */

/** Pamja kryesore e faqes (hub ose panel dokumenti/imazhi). */
export type ProductUploadView = 'hub' | 'document' | 'image';

/** Opsionet e kartave në hub. */
export type ProductUploadHubMode = 'document' | 'image' | 'manual';

/** Lloji i përpunimit të skedarëve në server (ekstraktim vs OCR). */
export type ProductFileKind = 'document' | 'image';

/** Mesazh global (AlertModal). */
export interface ProductUploadAlert {
  variant: 'success' | 'error' | 'info';
  title: string;
  message: string;
}

/** Tekstet e mbulesës së ngarkimit (LoadingOverlay). */
export interface ProductUploadProcessOverlay {
  title: string;
  description: string;
}

/** Vlerat e formës manuale (string për inpute të kontrolluara). */
export interface ProductManualFormValues {
  name: string;
  description: string;
  price: string;
  sku: string;
  category: string;
  stock: string;
  unit: string;
  tags: string;
}

export type ProductManualFieldErrors = Partial<Record<keyof ProductManualFormValues, string>>;

/** Përgjigje e thjeshtë nga shtresa API (para normalizimit Laravel). */
export type ProductUploadApiResult<T> =
  | { ok: true; data: T }
  | { ok: false; error: string; code?: string };

/** Rezultat pas ngarkimit në server (shumë skedarë mund të jenë njëpasnjëshëm). */
export interface ProductFileProcessPayload {
  /** Sa skedarë u dërguan me sukses në këtë operacion */
  uploadedFileCount: number;
  /** Sa rreshta produktesh u ruajtën gjithsej (pas ekstraktimit / AI) */
  savedItemCount: number;
  /** Përmbledhje e shkurtër e emrave të skedarëve */
  fileNamesSummary: string;
}

/** Payload për ruajtje manuale (pas validimit). */
export interface ManualProductPayload {
  name: string;
  description: string;
  price: string;
  sku: string;
  category: string;
  stock: string;
  unit: string;
  tags: string;
}

/** Gjendja e ekspozuar nga konteksti (për container hooks). */
export interface ProductUploadContextState {
  view: ProductUploadView;
  manualModalOpen: boolean;
  isProcessing: boolean;
  processOverlay: ProductUploadProcessOverlay;
  alert: ProductUploadAlert | null;
}
