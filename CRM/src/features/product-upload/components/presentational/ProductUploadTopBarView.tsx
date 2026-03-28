/**
 * Presentational: shiriti i sipërm (stil studio), pa lidhje me kontekst.
 */

import { ArrowLeft } from 'lucide-react';

export interface ProductUploadTopBarViewProps {
  title: string;
  subtitle: string;
  onBack: () => void;
}

export function ProductUploadTopBarView({ title, subtitle, onBack }: ProductUploadTopBarViewProps) {
  return (
    <header className="studio-top-bar product-upload-top-bar">
      <div className="studio-top-bar__left">
        <button type="button" className="studio-icon-btn" aria-label="Kthehu" onClick={onBack}>
          <ArrowLeft size={18} strokeWidth={2} />
        </button>
        <div className="studio-top-bar__titles">
          <h1 className="studio-top-bar__title">{title}</h1>
          <p className="studio-top-bar__subtitle">{subtitle}</p>
        </div>
      </div>
    </header>
  );
}
