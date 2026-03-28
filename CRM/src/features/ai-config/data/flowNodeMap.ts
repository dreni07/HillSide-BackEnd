import type { ConfigDomainId } from '../types/studio';

const DOMAIN_IDS: ConfigDomainId[] = [
  'ai-personality',
  'ai-restrictions',
  'ai-salesman',
  'expected-questions',
];

const SET = new Set<string>(DOMAIN_IDS);

export function flowNodeToDomainId(nodeId: string): ConfigDomainId | null {
  return SET.has(nodeId) ? (nodeId as ConfigDomainId) : null;
}

export function isFlowDomainNode(nodeId: string): boolean {
  return SET.has(nodeId);
}
