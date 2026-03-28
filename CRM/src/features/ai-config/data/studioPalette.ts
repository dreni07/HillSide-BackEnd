import type { ConfigDomainId, StudioPaletteItem } from '../types/studio';

/** Single configuration workspace (no fake multi-agent tabs). */
export const STUDIO_WORKSPACE_LABEL = 'AI configuration';

export const STUDIO_DOMAIN_ITEMS: StudioPaletteItem[] = [
  {
    id: 'ai-personality',
    label: 'AI Personality',
    category: 'personality',
    hint: 'Tone, response style, language, greetings, custom instructions (ai_personalities).',
  },
  {
    id: 'ai-restrictions',
    label: 'AI Restrictions',
    category: 'restrictions',
    hint: 'Topics, blocked wording, length limits, guidelines (ai_restrictions).',
  },
  {
    id: 'ai-salesman',
    label: 'AI Salesman',
    category: 'sales',
    hint: 'Approach, upsell, product and pricing copy, CTAs, objections (ai_salesmen).',
  },
  {
    id: 'expected-questions',
    label: 'Expected Questions',
    category: 'qa',
    hint: 'Exact shopper questions and the answers your AI should use (ai_expected_questions).',
  },
];

export const STUDIO_SECTION_LABELS: Record<StudioPaletteItem['category'], string> = {
  personality: 'Personality',
  restrictions: 'Restrictions',
  sales: 'Sales behaviour',
  qa: 'Exact Q & A',
};

const labelByDomain = new Map(STUDIO_DOMAIN_ITEMS.map((i) => [i.id, i]));

export function getDomainItem(id: ConfigDomainId): StudioPaletteItem | undefined {
  return labelByDomain.get(id);
}
