/**
 * Presentational: toolbar me buton “Zgjidh skedarë”.
 */

import { FolderOpen } from 'lucide-react';

export interface ProductFileToolbarViewProps {
  hint: string;
  disabled?: boolean;
  onPick: () => void;
  pickLabel?: string;
}

export function ProductFileToolbarView({
  hint,
  disabled,
  onPick,
  pickLabel = 'Zgjidh skedarë',
}: ProductFileToolbarViewProps) {
  return (
    <div className="product-upload-file-toolbar" role="toolbar" aria-label="Veglat e ngarkimit">
      <button
        type="button"
        className="studio-btn studio-btn--primary product-upload-toolbar-btn"
        onClick={onPick}
        disabled={disabled}
      >
        <FolderOpen size={16} strokeWidth={2} aria-hidden />
        {pickLabel}
      </button>
      <span className="product-upload-toolbar-hint">{hint}</span>
    </div>
  );
}
