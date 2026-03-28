/**
 * Konteksti i ngarkimit të produkteve: reducer për pamje / modal / alert,
 * dhe veprime asinkrone që thërrasin API-në e Laravel-it.
 */

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useReducer,
  type ReactNode,
} from 'react';
import {
  submitManualProduct as submitManualProductApi,
  uploadProductDocumentFile,
  uploadProductImageFile,
} from '../api/productUploadApi';
import { processOverlayForKind } from '../constants/productUpload.constants';
import { getEmptyFilesErrorMessage } from '../lib/fileUploadGuards';
import { summarizeFileNamesForDisplay } from '../lib/productUploadSummary';
import type {
  ManualProductPayload,
  ProductFileKind,
  ProductUploadAlert,
  ProductUploadContextState,
  ProductUploadHubMode,
  ProductUploadProcessOverlay,
  ProductUploadView,
} from '../types/productUpload.types';

type ProductUploadAction =
  | { type: 'SET_VIEW'; view: ProductUploadView }
  | { type: 'OPEN_MANUAL' }
  | { type: 'CLOSE_MANUAL' }
  | { type: 'PROCESS_START'; overlay: ProductUploadProcessOverlay }
  | { type: 'UPDATE_PROCESS_OVERLAY'; overlay: Partial<ProductUploadProcessOverlay> }
  | { type: 'PROCESS_END' }
  | { type: 'SET_ALERT'; alert: ProductUploadAlert | null };

const initialState: ProductUploadContextState = {
  view: 'hub',
  manualModalOpen: false,
  isProcessing: false,
  processOverlay: { title: '', description: '' },
  alert: null,
};

function productUploadReducer(
  state: ProductUploadContextState,
  action: ProductUploadAction,
): ProductUploadContextState {
  switch (action.type) {
    case 'SET_VIEW':
      return { ...state, view: action.view };
    case 'OPEN_MANUAL':
      return { ...state, manualModalOpen: true };
    case 'CLOSE_MANUAL':
      return { ...state, manualModalOpen: false };
    case 'PROCESS_START':
      return {
        ...state,
        isProcessing: true,
        processOverlay: action.overlay,
      };
    case 'UPDATE_PROCESS_OVERLAY':
      return {
        ...state,
        processOverlay: {
          ...state.processOverlay,
          ...action.overlay,
        },
      };
    case 'PROCESS_END':
      return { ...state, isProcessing: false };
    case 'SET_ALERT':
      return { ...state, alert: action.alert };
    default:
      return state;
  }
}

export interface ProductUploadContextValue extends ProductUploadContextState {
  goBack: () => void;
  selectHubMode: (mode: ProductUploadHubMode) => void;
  closeManualModal: () => void;
  dismissAlert: () => void;
  processUploadedFiles: (files: FileList, kind: ProductFileKind) => Promise<void>;
  commitManualProduct: (payload: ManualProductPayload) => Promise<boolean>;
}

const ProductUploadContext = createContext<ProductUploadContextValue | null>(null);

export interface ProductUploadProviderProps {
  children: ReactNode;
  businessId?: string | null;
}

export function ProductUploadProvider({ children, businessId = null }: ProductUploadProviderProps) {
  const [state, dispatch] = useReducer(productUploadReducer, initialState);

  const goBack = useCallback(() => {
    if (state.view !== 'hub') {
      dispatch({ type: 'SET_VIEW', view: 'hub' });
      return;
    }
    window.history.back();
  }, [state.view]);

  const selectHubMode = useCallback((mode: ProductUploadHubMode) => {
    if (mode === 'manual') {
      dispatch({ type: 'OPEN_MANUAL' });
      return;
    }
    dispatch({ type: 'SET_VIEW', view: mode === 'document' ? 'document' : 'image' });
  }, []);

  const closeManualModal = useCallback(() => {
    dispatch({ type: 'CLOSE_MANUAL' });
  }, []);

  const dismissAlert = useCallback(() => {
    dispatch({ type: 'SET_ALERT', alert: null });
  }, []);

  const processUploadedFiles = useCallback(
    async (files: FileList, kind: ProductFileKind) => {
      const list = Array.from(files);
      const emptyMsg = getEmptyFilesErrorMessage(list);
      if (emptyMsg) {
        dispatch({
          type: 'SET_ALERT',
          alert: {
            variant: 'error',
            title: 'Skedar i pavlefshëm',
            message: emptyMsg,
          },
        });
        return;
      }

      const bid = businessId?.trim();
      if (!bid) {
        dispatch({
          type: 'SET_ALERT',
          alert: {
            variant: 'error',
            title: 'Biznesi mungon',
            message: 'Mungon biznesi. Hyni përsëri ose plotësoni profilin e biznesit.',
          },
        });
        return;
      }

      const baseOverlay = processOverlayForKind(kind);
      const { fileNamesSummary, uploadedFileCount } = summarizeFileNamesForDisplay(list);

      dispatch({
        type: 'PROCESS_START',
        overlay: {
          ...baseOverlay,
          description:
            list.length > 1
              ? `Skedari 1 / ${list.length}: “${list[0].name}” — ekstraktim, OCR dhe AI (Groq)…`
              : `${baseOverlay.description} (“${list[0].name}”)`,
        },
      });

      let savedItemCount = 0;
      let completedFiles = 0;

      try {
        for (let i = 0; i < list.length; i++) {
          const file = list[i];
          if (i > 0) {
            dispatch({
              type: 'UPDATE_PROCESS_OVERLAY',
              overlay: {
                description: `Skedari ${i + 1} / ${list.length}: “${file.name}” — ekstraktim, OCR dhe AI (Groq)…`,
              },
            });
          }

          const data =
            kind === 'document'
              ? await uploadProductDocumentFile(bid, file)
              : await uploadProductImageFile(bid, file);
          savedItemCount += data.items?.length ?? 0;
          completedFiles += 1;
        }

        dispatch({ type: 'PROCESS_END' });
        dispatch({
          type: 'SET_ALERT',
          alert: {
            variant: 'success',
            title: 'Ngarkimi u përfundua',
            message:
              uploadedFileCount === 1
                ? `“${list[0].name}” — u ruajtën ${savedItemCount} rreshta produktesh në server.`
                : `U përpunuan ${completedFiles} skedarë (${fileNamesSummary}). Gjithsej ${savedItemCount} rreshta produktesh në bazë.`,
          },
        });
      } catch (e) {
        dispatch({ type: 'PROCESS_END' });
        const message = e instanceof Error ? e.message : 'Ngarkimi dështoi.';
        const partial =
          completedFiles > 0
            ? ` Para gabimit u përpunuan ${completedFiles} skedarë dhe u ruajtën ${savedItemCount} rreshta.`
            : '';
        dispatch({
          type: 'SET_ALERT',
          alert: {
            variant: 'error',
            title: 'Gabim gjatë ngarkimit',
            message: `${message}${partial}`,
          },
        });
      }
    },
    [businessId],
  );

  const commitManualProduct = useCallback(
    async (payload: ManualProductPayload) => {
      const result = await submitManualProductApi(payload, { businessId: businessId ?? undefined });
      if (!result.ok) {
        dispatch({
          type: 'SET_ALERT',
          alert: {
            variant: 'error',
            title: 'Ruajtja dështoi',
            message: result.error,
          },
        });
        return false;
      }
      dispatch({
        type: 'SET_ALERT',
        alert: {
          variant: 'success',
          title: 'Produkti u ruajt',
          message: `“${payload.name}”${payload.sku ? ` (SKU: ${payload.sku})` : ''} — ${result.data.itemsCount} regjistrim në bazë.`,
        },
      });
      dispatch({ type: 'CLOSE_MANUAL' });
      return true;
    },
    [businessId],
  );

  const value = useMemo<ProductUploadContextValue>(
    () => ({
      ...state,
      goBack,
      selectHubMode,
      closeManualModal,
      dismissAlert,
      processUploadedFiles,
      commitManualProduct,
    }),
    [
      state,
      goBack,
      selectHubMode,
      closeManualModal,
      dismissAlert,
      processUploadedFiles,
      commitManualProduct,
    ],
  );

  return <ProductUploadContext.Provider value={value}>{children}</ProductUploadContext.Provider>;
}

export function useProductUpload(): ProductUploadContextValue {
  const ctx = useContext(ProductUploadContext);
  if (!ctx) {
    throw new Error('useProductUpload duhet përdorur brenda ProductUploadProvider.');
  }
  return ctx;
}
