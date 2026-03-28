import type { ReactNode } from 'react';
import { X } from 'lucide-react';
import { useAiStudio } from '../../../hooks/useAiStudio';
import type { StudioInspectorHeaderModel, StudioPaletteCategory } from '../../../types/studio';
import { InspectorDomainForms } from './InspectorDomainForms';

function categoryAccentClass(cat: StudioPaletteCategory) {
  if (cat === 'personality') return 'studio-inspector-accent--personality';
  if (cat === 'restrictions') return 'studio-inspector-accent--restrictions';
  if (cat === 'sales') return 'studio-inspector-accent--sales';
  return 'studio-inspector-accent--qa';
}

function InspectorHeader({
  model,
  onClose,
}: {
  model: StudioInspectorHeaderModel;
  onClose: () => void;
}) {
  return (
    <header className="studio-inspector-header">
      <div className={`studio-inspector-header__icon ${categoryAccentClass(model.category)}`} aria-hidden />
      <div className="studio-inspector-header__text">
        <h2 className="studio-inspector-title">{model.title}</h2>
        <p className="studio-inspector-subtitle">{model.subtitle}</p>
      </div>
      <button type="button" className="studio-icon-btn studio-inspector-close" aria-label="Close panel" onClick={onClose}>
        <X size={18} />
      </button>
    </header>
  );
}

function StudioInspectorEmpty() {
  return (
    <aside className="studio-inspector-panel studio-inspector-panel--empty">
      <p className="studio-inspector-empty-text">Select a block in the library or on the canvas to edit configuration.</p>
    </aside>
  );
}

function StudioInspectorRoot({ children }: { children: ReactNode }) {
  return <aside className="studio-inspector-panel">{children}</aside>;
}

export const StudioInspector = {
  Root: StudioInspectorRoot,
  Header: InspectorHeader,
};

export function StudioInspectorPanel() {
  const { inspectorHeader, selectedDomainId, clearInspector } = useAiStudio();

  if (!inspectorHeader || !selectedDomainId) {
    return <StudioInspectorEmpty />;
  }

  return (
    <StudioInspectorRoot>
      <InspectorHeader model={inspectorHeader} onClose={clearInspector} />
      <InspectorDomainForms domainId={selectedDomainId} />
    </StudioInspectorRoot>
  );
}

StudioInspectorPanel.Empty = StudioInspectorEmpty;
StudioInspectorPanel.Inspector = StudioInspector;
