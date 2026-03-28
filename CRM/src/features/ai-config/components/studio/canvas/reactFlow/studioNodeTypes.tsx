import { memo } from 'react';
import { Handle, Position, type NodeProps } from 'reactflow';
import { flowNodeToDomainId } from '../../../../data/flowNodeMap';
import { useAiStudio } from '../../../../hooks/useAiStudio';
import type { StudioPaletteCategory } from '../../../../types/studio';

function useDomainNodeHighlight(nodeId: string, rfSelected: boolean) {
  const { selection } = useAiStudio();
  const domain = flowNodeToDomainId(nodeId);
  if (rfSelected) return true;
  if (!selection || !domain) return false;
  if (selection.source === 'palette') return selection.itemId === domain;
  return selection.nodeId === nodeId;
}

export const StudioDomainNode = memo(function StudioDomainNode({
  id,
  data,
  selected,
}: NodeProps<{ title: string; meta: string; accent: StudioPaletteCategory }>) {
  const highlighted = useDomainNodeHighlight(id, selected);
  const accentClass = `rf-studio-domain--${data.accent}`;
  return (
    <div className={`rf-studio-domain ${accentClass}${highlighted ? ' rf-studio-node--highlight' : ''}`}>
      <Handle type="target" position={Position.Top} id="t" className="rf-handle rf-handle--in" />
      <span className="rf-studio-domain-title">{data.title}</span>
      <span className="rf-studio-domain-meta">{data.meta}</span>
      <Handle type="source" position={Position.Bottom} id="s" className="rf-handle rf-handle--out" />
    </div>
  );
});

export const studioFlowNodeTypes = {
  studioDomain: StudioDomainNode,
} as const;
