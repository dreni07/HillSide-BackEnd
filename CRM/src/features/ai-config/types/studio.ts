import type { AiResponseStyleValue, AiToneValue, SalesApproachValue } from '../data/aiSchemaConstants';

export type ConfigDomainId =
  | 'ai-personality'
  | 'ai-restrictions'
  | 'ai-salesman'
  | 'expected-questions';

export type StudioPaletteCategory = 'personality' | 'restrictions' | 'sales' | 'qa';

export interface StudioPaletteItem {
  id: ConfigDomainId;
  label: string;
  category: StudioPaletteCategory;
  /** Shown in inspector; includes table name for clarity. */
  hint?: string;
}

export type StudioSelection =
  | { source: 'palette'; itemId: ConfigDomainId }
  | { source: 'canvas'; nodeId: string }
  | null;

/** `ai_personalities` row shape (draft). */
export interface AiPersonalityDraft {
  tone: AiToneValue;
  response_style: AiResponseStyleValue;
  language: string;
  greeting_message: string;
  farewell_message: string;
  custom_instructions: string;
}

/** `ai_restrictions` — JSON columns edited as string lists in the UI. */
export interface AiRestrictionsDraft {
  allowed_topics: string[];
  restricted_topics: string[];
  blocked_words: string[];
  max_response_length: number | null;
  content_guidelines: string;
}

/** `ai_salesmen` row shape (draft). */
export interface AiSalesmanDraft {
  sales_approach: SalesApproachValue;
  upsell_enabled: boolean;
  product_knowledge: string;
  pricing_info: string;
  call_to_action: string;
  objection_handling: string;
}

/** `ai_expected_questions` — client id until persisted. */
export interface ExpectedQuestionDraft {
  clientId: string;
  question: string;
  answer: string;
}

export interface StudioInspectorHeaderModel {
  title: string;
  /** Short description of what this panel configures. */
  subtitle: string;
  category: StudioPaletteCategory;
}

/** `ai_behaviours` — studio shell + flow persistence (subset used by the CRM UI). */
export interface AiBehaviourDraft {
  orchestration_title: string;
  orchestration_subtitle: string | null;
  flow_graph_json: string | null;
}
