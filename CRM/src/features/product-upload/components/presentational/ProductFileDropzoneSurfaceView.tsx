/**
 * Presentational: sipërfaqja vizuale e dropzones (animacion klasa nga prindi).
 */

import { Upload } from 'lucide-react';

export interface ProductFileDropzoneSurfaceViewProps {
  title: string;
  isDragging: boolean;
  disabled?: boolean;
  helpId: string;
  onClick: () => void;
  onDragOver: (e: React.DragEvent) => void;
  onDragLeave: (e: React.DragEvent) => void;
  onDrop: (e: React.DragEvent) => void;
}

export function ProductFileDropzoneSurfaceView({
  title,
  isDragging,
  disabled,
  helpId,
  onClick,
  onDragOver,
  onDragLeave,
  onDrop,
}: ProductFileDropzoneSurfaceViewProps) {
  return (
    <button
      type="button"
      className={`product-upload-dropzone${isDragging ? ' product-upload-dropzone--active' : ''}`}
      onDragOver={onDragOver}
      onDragLeave={onDragLeave}
      onDrop={onDrop}
      onClick={onClick}
      disabled={disabled}
      aria-describedby={helpId}
    >
      <Upload className="product-upload-dropzone__icon" size={28} strokeWidth={1.75} aria-hidden />
      <span className="product-upload-dropzone__title">{title}</span>
      <span id={helpId} className="product-upload-dropzone__sub">
        Tërhiq &amp; lësho këtu ose kliko për të hapur zgjedhësin e skedarëve.
      </span>
    </button>
  );
}
