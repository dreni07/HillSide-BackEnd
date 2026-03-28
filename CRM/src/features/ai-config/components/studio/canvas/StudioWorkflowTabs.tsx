import { STUDIO_WORKSPACE_LABEL } from '../../../data/studioPalette';

export function StudioWorkflowTabs() {
  return (
    <div className="studio-workflow-tabs studio-workflow-tabs--single">
      <span className="studio-workflow-tabs__static">{STUDIO_WORKSPACE_LABEL}</span>
    </div>
  );
}
