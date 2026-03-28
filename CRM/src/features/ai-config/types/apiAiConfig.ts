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

/** `ai_behaviours` row — orchestration studio + flow snapshot. */
export interface ApiAiBehaviour {
  id: number;
  business_id: number;
  orchestration_title: string;
  orchestration_subtitle: string | null;
  insight_banner_message: string | null;
  active_workflow_tab: string | null;
  flow_graph_json: string | null;
  selected_palette_item_id: string | null;
  inspector_detail_json: Record<string, unknown> | null;
  personality_summary: string | null;
  customer_restriction_rules: string | null;
  sales_objectives: string | null;
  voice_tone: string | null;
  implementation_method: string | null;
  scenario_flags_json: unknown[] | Record<string, unknown> | null;
  goals_maintain_json: unknown[] | Record<string, unknown> | null;
  goals_minimize_json: unknown[] | Record<string, unknown> | null;
  constraints_notes: string | null;
  perception_modules_snapshot_json: unknown[] | Record<string, unknown> | null;
  selector_modules_snapshot_json: unknown[] | Record<string, unknown> | null;
  skill_modules_snapshot_json: unknown[] | Record<string, unknown> | null;
  is_published: boolean;
}

export interface ApiAiConfigShowData {
  config: ApiAiConfigRow | null;
  expected_questions: ApiExpectedQuestionRow[];
  behaviour: ApiAiBehaviour | null;
}

/** Response body from POST …/ai-config/save */
export interface ApiAiConfigSaveData {
  config: ApiAiConfigRow;
  expected_questions: ApiExpectedQuestionRow[];
  behaviour: ApiAiBehaviour | null;
}

export type SaveToServerResult =
  | { ok: true; message: string }
  | { ok: false; message: string };
