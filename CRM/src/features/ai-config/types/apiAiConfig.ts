/** Laravel `ai_configs` + relations as returned by the API (enums as strings). */

export interface ApiAiPersonality {
  id: number;
  tone: string;
  response_style: string;
  language: string;
  greeting_message: string | null;
  farewell_message: string | null;
  custom_instructions: string | null;
}

export interface ApiAiRestrictions {
  id: number;
  allowed_topics: string[] | null;
  restricted_topics: string[] | null;
  blocked_words: string[] | null;
  max_response_length: number | null;
  content_guidelines: string | null;
}

export interface ApiAiSalesman {
  id: number;
  sales_approach: string;
  upsell_enabled: boolean;
  product_knowledge: string | null;
  pricing_info: string | null;
  call_to_action: string | null;
  objection_handling: string | null;
}

export interface ApiAiConfigRow {
  id: number;
  business_id: number;
  ai_personality_id: number;
  ai_restrictions_id: number;
  ai_salesman_id: number;
  is_active: boolean;
  personality: ApiAiPersonality;
  restrictions: ApiAiRestrictions;
  salesman: ApiAiSalesman;
}

export interface ApiExpectedQuestionRow {
  id: number;
  business_id: number;
  question: string;
  answer: string;
  sort_order: number;
}

export interface ApiAiConfigShowData {
  config: ApiAiConfigRow | null;
  expected_questions: ApiExpectedQuestionRow[];
}

/** Response body from POST …/ai-config/save */
export interface ApiAiConfigSaveData {
  config: ApiAiConfigRow;
  expected_questions: ApiExpectedQuestionRow[];
}

export type SaveToServerResult =
  | { ok: true; message: string }
  | { ok: false; message: string };
