import { MarkerType, type Edge, type Node } from 'reactflow';

export const STUDIO_FLOW_NODE_IDS = {
  personality: 'ai-personality',
  restrictions: 'ai-restrictions',
  salesman: 'ai-salesman',
  expectedQuestions: 'expected-questions',
} as const;

const blueArrow = {
  type: 'smoothstep' as const,
  style: { stroke: '#2563eb', strokeWidth: 2 },
  markerEnd: {
    type: MarkerType.ArrowClosed,
    color: '#2563eb',
    width: 18,
    height: 18,
  },
};

export const initialStudioNodes: Node[] = [
  {
    id: STUDIO_FLOW_NODE_IDS.personality,
    type: 'studioDomain',
    position: { x: 210, y: 32 },
    zIndex: 2,
    data: {
      title: 'AI Personality',
      meta: 'ai_personalities · tone, style, greetings',
      accent: 'personality' as const,
    },
  },
  {
    id: STUDIO_FLOW_NODE_IDS.restrictions,
    type: 'studioDomain',
    position: { x: 210, y: 168 },
    zIndex: 2,
    data: {
      title: 'AI Restrictions',
      meta: 'ai_restrictions · topics & limits',
      accent: 'restrictions' as const,
    },
  },
  {
    id: STUDIO_FLOW_NODE_IDS.salesman,
    type: 'studioDomain',
    position: { x: 210, y: 304 },
    zIndex: 2,
    data: {
      title: 'AI Salesman',
      meta: 'ai_salesmen · offers & objections',
      accent: 'sales' as const,
    },
  },
  {
    id: STUDIO_FLOW_NODE_IDS.expectedQuestions,
    type: 'studioDomain',
    position: { x: 190, y: 440 },
    zIndex: 2,
    style: { width: 260 },
    data: {
      title: 'Expected Questions',
      meta: 'ai_expected_questions · exact Q → A',
      accent: 'qa' as const,
    },
  },
];

export const initialStudioEdges: Edge[] = [
  {
    id: 'e-personality-restrictions',
    source: STUDIO_FLOW_NODE_IDS.personality,
    target: STUDIO_FLOW_NODE_IDS.restrictions,
    ...blueArrow,
  },
  {
    id: 'e-restrictions-salesman',
    source: STUDIO_FLOW_NODE_IDS.restrictions,
    target: STUDIO_FLOW_NODE_IDS.salesman,
    ...blueArrow,
  },
  {
    id: 'e-salesman-questions',
    source: STUDIO_FLOW_NODE_IDS.salesman,
    target: STUDIO_FLOW_NODE_IDS.expectedQuestions,
    ...blueArrow,
  },
];
