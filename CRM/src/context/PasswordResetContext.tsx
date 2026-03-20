import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useReducer,
  useRef,
} from 'react';
import type { ReactNode } from 'react';
import {
  mockRequestPasswordReset,
  mockResetPassword,
  mockVerifyPasswordResetCode,
} from '../requests/passwordResetRequests';
import type {
  PasswordResetContextState,
  PasswordResetRequestResult,
} from '../types/passwordReset';
import type { PasswordResetStep } from '../types/passwordReset';

type PasswordResetAction =
  | { type: 'REQUEST_START' }
  | { type: 'REQUEST_SUCCESS'; payload: PasswordResetRequestResult; requestedAt: number }
  | { type: 'REQUEST_ERROR'; error: string }
  | { type: 'VERIFY_START' }
  | { type: 'VERIFY_SUCCESS' }
  | { type: 'VERIFY_ERROR'; error: string }
  | { type: 'RESET_START' }
  | { type: 'RESET_SUCCESS'; success: string }
  | { type: 'RESET_ERROR'; error: string }
  | { type: 'CLEAR_MESSAGES' }
  | { type: 'HYDRATE'; payload: Partial<PasswordResetContextState> };

type PasswordResetContextValue = PasswordResetContextState & {
  requestReset: (email: string) => Promise<void>;
  verifyCode: (code: string) => Promise<void>;
  resetPassword: (password: string) => Promise<void>;
  resendCode: () => Promise<void>;
  clearMessages: () => void;
  clearFlow: () => void;
};

const STORAGE = {
  email: 'crm_pwreset_email',
  otp: 'crm_pwreset_otp',
  requestedAt: 'crm_pwreset_requested_at',
  step: 'crm_pwreset_step',
};

const MAX_AGE_MS = 10 * 60 * 1000; // 10 min (front-end only dummy)

const initialState: PasswordResetContextState = {
  email: null,
  expectedOtp: null,
  requestedAt: null,
  step: 'idle',
  loading: {
    requesting: false,
    verifying: false,
    resetting: false,
  },
  lastSuccess: null,
  lastError: null,
};

function readStorage(): Partial<PasswordResetContextState> {
  try {
    const email = localStorage.getItem(STORAGE.email);
    const expectedOtp = localStorage.getItem(STORAGE.otp);
    const requestedAtRaw = localStorage.getItem(STORAGE.requestedAt);
    const stepRaw = localStorage.getItem(STORAGE.step) as PasswordResetStep | null;

    if (!email || !expectedOtp || !requestedAtRaw || !stepRaw) return {};

    const requestedAt = Number(requestedAtRaw);
    if (!Number.isFinite(requestedAt)) return {};

    const expired = Date.now() - requestedAt > MAX_AGE_MS;
    if (expired) return {};

    const step: PasswordResetStep = stepRaw === 'verified' ? 'verified' : 'code_sent';
    return { email, expectedOtp, requestedAt, step };
  } catch {
    return {};
  }
}

function clearStorage() {
  try {
    localStorage.removeItem(STORAGE.email);
    localStorage.removeItem(STORAGE.otp);
    localStorage.removeItem(STORAGE.requestedAt);
    localStorage.removeItem(STORAGE.step);
  } catch {
    // ignore
  }
}

function reducer(state: PasswordResetContextState, action: PasswordResetAction): PasswordResetContextState {
  switch (action.type) {
    case 'HYDRATE':
      return {
        ...state,
        ...action.payload,
        loading: { ...state.loading, requesting: false, verifying: false, resetting: false },
      };
    case 'CLEAR_MESSAGES':
      return { ...state, lastError: null, lastSuccess: null };

    case 'REQUEST_START':
      return { ...state, lastError: null, lastSuccess: null, loading: { ...state.loading, requesting: true } };
    case 'REQUEST_SUCCESS':
      return {
        ...state,
        email: action.payload.email,
        expectedOtp: action.payload.otp,
        requestedAt: action.requestedAt,
        step: 'code_sent',
        lastError: null,
        lastSuccess: 'Kod u dërgua (mock). Fut kodin për me vazhdu.',
        loading: { ...state.loading, requesting: false },
      };
    case 'REQUEST_ERROR':
      return {
        ...state,
        lastError: action.error,
        lastSuccess: null,
        loading: { ...state.loading, requesting: false },
      };

    case 'VERIFY_START':
      return { ...state, lastError: null, lastSuccess: null, loading: { ...state.loading, verifying: true } };
    case 'VERIFY_SUCCESS':
      return {
        ...state,
        step: 'verified',
        lastError: null,
        lastSuccess: 'Kodi u verifikua (mock). Tani vendos fjalëkalim të ri.',
        loading: { ...state.loading, verifying: false },
      };
    case 'VERIFY_ERROR':
      return {
        ...state,
        lastError: action.error,
        lastSuccess: null,
        loading: { ...state.loading, verifying: false },
      };

    case 'RESET_START':
      return { ...state, lastError: null, lastSuccess: null, loading: { ...state.loading, resetting: true } };
    case 'RESET_SUCCESS':
      return {
        ...state,
        lastError: null,
        lastSuccess: action.success,
        loading: { ...state.loading, resetting: false },
      };
    case 'RESET_ERROR':
      return {
        ...state,
        lastError: action.error,
        lastSuccess: null,
        loading: { ...state.loading, resetting: false },
      };
    default:
      return state;
  }
}

export const PasswordResetContext = createContext<PasswordResetContextValue | null>(null);

export function PasswordResetProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(reducer, initialState);

  const hydratedOnce = useRef(false);
  useEffect(() => {
    if (hydratedOnce.current) return;
    hydratedOnce.current = true;
    const payload = readStorage();
    if (Object.keys(payload).length > 0) dispatch({ type: 'HYDRATE', payload });
  }, []);

  const clearFlow = useCallback(() => {
    clearStorage();
    dispatch({
      type: 'HYDRATE',
      payload: { email: null, expectedOtp: null, requestedAt: null, step: 'idle' },
    });
  }, []);

  const clearMessages = useCallback(() => {
    dispatch({ type: 'CLEAR_MESSAGES' });
  }, []);

  const requestReset = useCallback(async (email: string) => {
    dispatch({ type: 'REQUEST_START' });
    try {
      const res = await mockRequestPasswordReset(email);
      const requestedAt = Date.now();
      try {
        localStorage.setItem(STORAGE.email, res.email);
        localStorage.setItem(STORAGE.otp, res.otp);
        localStorage.setItem(STORAGE.requestedAt, String(requestedAt));
        localStorage.setItem(STORAGE.step, 'code_sent');
      } catch {
        // ignore
      }

      dispatch({ type: 'REQUEST_SUCCESS', payload: res, requestedAt });
    } catch (err) {
      dispatch({ type: 'REQUEST_ERROR', error: err instanceof Error ? err.message : 'Gabim në request.' });
      throw err;
    }
  }, []);

  const verifyCode = useCallback(async (code: string) => {
    dispatch({ type: 'VERIFY_START' });
    try {
      if (!state.email || !state.expectedOtp || !state.requestedAt || state.step !== 'code_sent') {
        throw new Error('Filloni nga “Forgot Password” përpara se me verifiku kodin.');
      }

      await mockVerifyPasswordResetCode({ code, expectedOtp: state.expectedOtp, email: state.email });

      try {
        localStorage.setItem(STORAGE.step, 'verified');
      } catch {
        // ignore
      }

      dispatch({ type: 'VERIFY_SUCCESS' });
    } catch (err) {
      dispatch({ type: 'VERIFY_ERROR', error: err instanceof Error ? err.message : 'Gabim gjatë verifikimit.' });
      throw err;
    }
  }, [state.email, state.expectedOtp, state.requestedAt, state.step]);

  const resetPassword = useCallback(
    async (password: string) => {
      dispatch({ type: 'RESET_START' });
      try {
        if (!state.email || state.step !== 'verified') {
          throw new Error('Ju lutem verifikoni kodin përpara se me resetu fjalëkalimin.');
        }

        await mockResetPassword({ email: state.email, password });

        dispatch({ type: 'RESET_SUCCESS', success: 'Fjalëkalimi u resetua (mock). Tani mund të kyçeni.' });
        clearFlow();
      } catch (err) {
        dispatch({ type: 'RESET_ERROR', error: err instanceof Error ? err.message : 'Gabim gjatë reset.' });
        throw err;
      }
    },
    [state.email, state.step, clearFlow]
  );

  const resendCode = useCallback(async () => {
    if (!state.email) throw new Error('Nuk ka email në flow. Kthehu te Forgot Password.');
    await requestReset(state.email);
  }, [state.email, requestReset]);

  const value = useMemo<PasswordResetContextValue>(
    () => ({
      ...state,
      requestReset,
      verifyCode,
      resetPassword,
      resendCode,
      clearMessages,
      clearFlow,
    }),
    [state, requestReset, verifyCode, resetPassword, resendCode, clearFlow, clearMessages]
  );

  return <PasswordResetContext.Provider value={value}>{children}</PasswordResetContext.Provider>;
}

export function usePasswordResetFlow(): PasswordResetContextValue {
  const ctx = useContext(PasswordResetContext);
  if (!ctx) throw new Error('usePasswordResetFlow duhet përdorur brenda PasswordResetProvider');
  return ctx;
}

