export const AI_TONE_VALUES = [
  'professional',
  'friendly',
  'casual',
  'formal',
  'enthusiastic',
] as const;

export const AI_RESPONSE_STYLE_VALUES = ['concise', 'balanced', 'detailed'] as const;

export const SALES_APPROACH_VALUES = [
  'consultative',
  'soft_sell',
  'direct',
  'relationship_based',
] as const;

export const AI_TONE_LABELS: Record<(typeof AI_TONE_VALUES)[number], string> = {
  professional: 'Professional',
  friendly: 'Friendly',
  casual: 'Casual',
  formal: 'Formal',
  enthusiastic: 'Enthusiastic',
};

export const AI_RESPONSE_STYLE_LABELS: Record<(typeof AI_RESPONSE_STYLE_VALUES)[number], string> = {
  concise: 'Concise',
  balanced: 'Balanced',
  detailed: 'Detailed',
};

export const SALES_APPROACH_LABELS: Record<(typeof SALES_APPROACH_VALUES)[number], string> = {
  consultative: 'Consultative',
  soft_sell: 'Soft Sell',
  direct: 'Direct',
  relationship_based: 'Relationship Based',
};

export type AiToneValue = (typeof AI_TONE_VALUES)[number];
export type AiResponseStyleValue = (typeof AI_RESPONSE_STYLE_VALUES)[number];
export type SalesApproachValue = (typeof SALES_APPROACH_VALUES)[number];
