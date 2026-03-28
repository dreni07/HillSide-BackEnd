import type { Edge, Node } from 'reactflow';

/**
 * Parses persisted React Flow `toObject()` JSON from the API; falls back if invalid.
 */
export function parseStudioFlowJson(
  raw: string | null | undefined,
  fallbackNodes: Node[],
  fallbackEdges: Edge[],
): { nodes: Node[]; edges: Edge[] } {
  if (raw == null || raw.trim() === '') {
    return { nodes: fallbackNodes, edges: fallbackEdges };
  }
  try {
    const parsed = JSON.parse(raw) as { nodes?: unknown; edges?: unknown };
    const nodes = Array.isArray(parsed.nodes) ? (parsed.nodes as Node[]) : null;
    const edges = Array.isArray(parsed.edges) ? (parsed.edges as Edge[]) : null;
    if (nodes && edges) {
      return { nodes, edges };
    }
  } catch {
    /* ignore */
  }
  return { nodes: fallbackNodes, edges: fallbackEdges };
}
