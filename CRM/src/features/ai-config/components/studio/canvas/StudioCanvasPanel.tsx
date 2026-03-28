import { StudioFlowDiagram } from './StudioFlowDiagram';
import { StudioWorkflowTabs } from './StudioWorkflowTabs';

export function StudioCanvasPanel() {
  return (
    <section className="studio-canvas-panel" aria-label="Orchestration canvas">
      <StudioWorkflowTabs />
      <div className="studio-canvas-surface">
        <StudioFlowDiagram />
      </div>
    </section>
  );
}
