import {
  AI_RESPONSE_STYLE_VALUES,
  AI_TONE_VALUES,
  SALES_APPROACH_VALUES,
} from '../data/aiSchemaConstants';
import type { ApiAiConfigShowData, ApiExpectedQuestionRow } from '../types/apiAiConfig';
import type {
  AiBehaviourDraft,
  AiPersonalityDraft,
  AiRestrictionsDraft,
  AiSalesmanDraft,
  ExpectedQuestionDraft,
} from '../types/studio';

function coerceTone(v: string): AiPersonalityDraft['tone'] {
  return (AI_TONE_VALUES as readonly string[]).includes(v)
    ? (v as AiPersonalityDraft['tone'])
    : 'professional';
}

function coerceStyle(v: string): AiPersonalityDraft['response_style'] {
  return (AI_RESPONSE_STYLE_VALUES as readonly string[]).includes(v)
    ? (v as AiPersonalityDraft['response_style'])
    : 'balanced';
}

function coerceApproach(v: string): AiSalesmanDraft['sales_approach'] {
  return (SALES_APPROACH_VALUES as readonly string[]).includes(v)
    ? (v as AiSalesmanDraft['sales_approach'])
    : 'consultative';
}

function mapBehaviourDraft(b: ApiAiConfigShowData['behaviour']): AiBehaviourDraft {
  if (!b) {
    return {
      orchestration_title: 'Agent Orchestration Studio',
      orchestration_subtitle: 'Configure AI for your business',
      flow_graph_json: null,
    };
  }
  return {
    orchestration_title: (b.orchestration_title && b.orchestration_title.trim()) || 'Agent Orchestration Studio',
    orchestration_subtitle: b.orchestration_subtitle ?? null,
    flow_graph_json: b.flow_graph_json ?? null,
  };
}

export function mapShowDataToDrafts(data: ApiAiConfigShowData): {
  personality: AiPersonalityDraft;
  restrictions: AiRestrictionsDraft;
  salesman: AiSalesmanDraft;
  expectedQuestions: ExpectedQuestionDraft[];
  behaviour: AiBehaviourDraft;
} {
  const config = data.config;
  if (!config) {
    return {
      personality: {
        tone: 'professional',
        response_style: 'balanced',
        language: 'en',
        greeting_message: '',
        farewell_message: '',
        custom_instructions: '',
      },
      restrictions: {
        allowed_topics: [],
        restricted_topics: [],
        blocked_words: [],
        max_response_length: null,
        content_guidelines: '',
      },
      salesman: {
        sales_approach: 'consultative',
        upsell_enabled: false,
        product_knowledge: '',
        pricing_info: '',
        call_to_action: '',
        objection_handling: '',
      },
      expectedQuestions: Array.isArray(data.expected_questions)
        ? mapExpectedQuestions(data.expected_questions)
        : [],
      behaviour: mapBehaviourDraft(data.behaviour ?? null),
    };
  }

  const p = config.personality;
  const r = config.restrictions;
  const s = config.salesman;

  return {
    personality: {
      tone: coerceTone(String(p.tone)),
      response_style: coerceStyle(String(p.response_style)),
      language: p.language || 'en',
      greeting_message: p.greeting_message ?? '',
      farewell_message: p.farewell_message ?? '',
      custom_instructions: p.custom_instructions ?? '',
    },
    restrictions: {
      allowed_topics: Array.isArray(r.allowed_topics) ? [...r.allowed_topics] : [],
      restricted_topics: Array.isArray(r.restricted_topics) ? [...r.restricted_topics] : [],
      blocked_words: Array.isArray(r.blocked_words) ? [...r.blocked_words] : [],
      max_response_length: r.max_response_length ?? null,
      content_guidelines: r.content_guidelines ?? '',
    },
    salesman: {
      sales_approach: coerceApproach(String(s.sales_approach)),
      upsell_enabled: Boolean(s.upsell_enabled),
      product_knowledge: s.product_knowledge ?? '',
      pricing_info: s.pricing_info ?? '',
      call_to_action: s.call_to_action ?? '',
      objection_handling: s.objection_handling ?? '',
    },
    expectedQuestions: Array.isArray(data.expected_questions)
      ? mapExpectedQuestions(data.expected_questions)
      : [],
    behaviour: mapBehaviourDraft(data.behaviour ?? null),
  };
}

function mapExpectedQuestions(rows: ApiExpectedQuestionRow[]): ExpectedQuestionDraft[] {
  return rows.map((row) => ({
    clientId: `srv_${row.id}`,
    question: row.question,
    answer: row.answer,
  }));
}
