export type PasswordResetStep = 'idle' | 'code_sent' | 'verified';

export interface PasswordResetContextState {
  email: string | null;
  expectedOtp: string | null;
  requestedAt: number | null;
  step: PasswordResetStep;
  loading: {
    requesting: boolean;
    verifying: boolean;
    resetting: boolean;
  };
  lastSuccess: string | null;
  lastError: string | null;
}

export interface PasswordResetRequestResult {
  email: string;
  otp: string;
}

export interface MockVerifyResult {
  verified: true;
}

export interface MockResetResult {
  reset: true;
}

export interface ForgotPasswordFormValues {
  email: string;
}

export interface VerifyCodeFormValues {
  code: string;
}

export interface ResetPasswordFormValues {
  password: string;
  confirmPassword: string;
}

