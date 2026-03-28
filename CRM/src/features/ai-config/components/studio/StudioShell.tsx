import { StudioCanvasPanel } from './canvas/StudioCanvasPanel';
import { StudioInspectorPanel } from './inspector/StudioInspectorPanel';
import { StudioLibraryPanel } from './library/StudioLibraryPanel';
import { StudioIconRail } from './rail/StudioIconRail';
import { StudioTopBarConnected } from './top-bar/StudioTopBarConnected';

export function StudioShell() {
  return (
    <div className="studio-shell">
      <StudioTopBarConnected />
      <StudioIconRail />
      <StudioLibraryPanel />
      <StudioCanvasPanel />
      <StudioInspectorPanel />
    </div>
  );
}
