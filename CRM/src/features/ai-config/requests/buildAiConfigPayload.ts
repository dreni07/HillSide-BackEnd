import type {
  AiBehaviourDraft,
  AiPersonalityDraft,
  AiRestrictionsDraft,
  AiSalesmanDraft,
  ExpectedQuestionDraft,
} from '../types/studio';

export interface SaveAiConfigPayload {
  personality: {
    tone: string;
    response_style: string;
    language: string;
    greeting_message: string | null;
    farewell_message: string | null;
    custom_instructions: string | null;
  };
  restrictions: {
    allowed_topics: string[];
    restricted_topics: string[];
    blocked_words: string[];
    max_response_length: number | null;
    content_guidelines: string | null;
  };
  salesman: {
    sales_approach: string;
    upsell_enabled: boolean;
    product_knowledge: string | null;
    pricing_info: string | null;
    call_to_action: string | null;
    objection_handling: string | null;
  };
  expected_questions: { question: string; answer: string }[];
  is_active: boolean;
  behaviour: {
    orchestration_title: string;
    orchestration_subtitle: string | null;
    flow_graph_json: string | null;
  };
}

export function buildSaveAiConfigPayload(
  personality: AiPersonalityDraft,
  restrictions: AiRestrictionsDraft,
  salesman: AiSalesmanDraft,
  expectedQuestions: ExpectedQuestionDraft[],
  behaviourDraft: AiBehaviourDraft,
  /** Live React Flow snapshot; omit or pass `undefined` if the canvas is not mounted. */
  flowGraphJsonFromCanvas?: string | null,
): SaveAiConfigPayload {
  const pairs = expectedQuestions
    .map((row) => ({
      question: row.question.trim(),
      answer: row.answer.trim(),
    }))
    .filter((row) => row.question.length > 0 && row.answer.length > 0);

  const flow_graph_json =
    flowGraphJsonFromCanvas != null ? flowGraphJsonFromCanvas : behaviourDraft.flow_graph_json;

  return {
    personality: {
      tone: personality.tone,
      response_style: personality.response_style,
      language: personality.language.trim() || 'en',
      greeting_message: personality.greeting_message.trim() || null,
      farewell_message: personality.farewell_message.trim() || null,
      custom_instructions: personality.custom_instructions.trim() || null,
    },
    restrictions: {
      allowed_topics: restrictions.allowed_topics,
      restricted_topics: restrictions.restricted_topics,
      blocked_words: restrictions.blocked_words,
      max_response_length: restrictions.max_response_length,
      content_guidelines: restrictions.content_guidelines.trim() || null,
    },
    salesman: {
      sales_approach: salesman.sales_approach,
      upsell_enabled: salesman.upsell_enabled,
      product_knowledge: salesman.product_knowledge.trim() || null,
      pricing_info: salesman.pricing_info.trim() || null,
      call_to_action: salesman.call_to_action.trim() || null,
      objection_handling: salesman.objection_handling.trim() || null,
    },
    expected_questions: pairs,
    is_active: true,
    behaviour: {
      orchestration_title:
        behaviourDraft.orchestration_title.trim() || 'Agent Orchestration Studio',
      orchestration_subtitle: behaviourDraft.orchestration_subtitle?.trim() || null,
      flow_graph_json,
    },
  };
}
