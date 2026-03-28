import { ArrowLeft } from 'lucide-react';

export interface StudioTopBarViewProps {
  title: string;
  subtitle: string;
  onBack?: () => void;
  onSave?: () => void;
  saveDisabled?: boolean;
}

export function StudioTopBarView({
  title,
  subtitle,
  onBack,
  onSave,
  saveDisabled,
}: StudioTopBarViewProps) {
  return (
    <header className="studio-top-bar">
      <div className="studio-top-bar__left">
        <button type="button" className="studio-icon-btn" aria-label="Back" onClick={onBack}>
          <ArrowLeft size={18} strokeWidth={2} />
        </button>
        <div className="studio-top-bar__titles">
          <h1 className="studio-top-bar__title">{title}</h1>
          <p className="studio-top-bar__subtitle">{subtitle}</p>
        </div>
      </div>

      <div className="studio-top-bar__right">
        <button
          type="button"
          className="studio-link-btn"
          onClick={onSave}
          disabled={saveDisabled}
        >
          Save
        </button>
      </div>
    </header>
  );
}
