import { EMAIL_RE } from '../constants/onboarding';
import type { MockResetResult, MockVerifyResult, PasswordResetRequestResult } from '../types/passwordReset';

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function randomOtp6(): string {
  return String(Math.floor(100000 + Math.random() * 900000));
}

export async function mockRequestPasswordReset(email: string): Promise<PasswordResetRequestResult> {
  await sleep(700);

  const trimmed = email.trim();
  if (!trimmed) throw new Error('Email është i detyrueshëm.');
  if (!EMAIL_RE.test(trimmed)) throw new Error('Ju lutem vendosni një email të vlefshëm.');

  // Dummy OTP (front-end only).
  return { email: trimmed, otp: randomOtp6() };
}

export async function mockVerifyPasswordResetCode(params: {
  code: string;
  expectedOtp: string;
  email: string;
}): Promise<MockVerifyResult> {
  await sleep(600);

  const code = params.code.trim();
  if (!/^\d{6}$/.test(code)) throw new Error('Kodi duhet të jetë 6 shifra.');

  if (code !== params.expectedOtp) throw new Error('Kodi nuk është i saktë. Provoni përsëri.');

  // Dummy success.
  void params.email;
  return { verified: true };
}

export async function mockResetPassword(params: {
  email: string;
  password: string;
}): Promise<MockResetResult> {
  await sleep(900);

  const password = params.password;
  if (!password || password.length < 8) throw new Error('Fjalëkalimi duhet të ketë së paku 8 karaktere.');

  // In production backend would handle hashing & updating.
  void params.email;
  return { reset: true };
}

