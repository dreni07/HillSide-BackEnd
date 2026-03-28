/**
 * Presentational: rrjeta e kartave të hub-it (tekste nga konstantet).
 */

import { FileText, Image as ImageIcon, PenLine, type LucideIcon } from 'lucide-react';
import { HUB_CARD_COPY } from '../../constants/productUpload.constants';
import type { ProductUploadHubMode } from '../../types/productUpload.types';

const HUB_ICONS: Record<ProductUploadHubMode, LucideIcon> = {
  document: FileText,
  image: ImageIcon,
  manual: PenLine,
};

const HUB_MODES = Object.keys(HUB_CARD_COPY) as ProductUploadHubMode[];

export interface ProductUploadHubViewProps {
  onSelect: (mode: ProductUploadHubMode) => void;
}

export function ProductUploadHubView({ onSelect }: ProductUploadHubViewProps) {
  return (
    <div className="product-upload-hub">
      <div className="product-upload-hub__intro">
        <h2 className="product-upload-hub__heading">Si dëshironi të shtoni produktin?</h2>
        <p className="product-upload-hub__lede">
          Dokumentet dhe imazhet ngarkohen në server (ekstraktim, OCR, AI Groq). Forma manuale ruan produktin
          direkt në bazë. Mesazhet e suksesit ose gabimit shfaqen pas çdo veprimi.
        </p>
      </div>
      <ul className="product-upload-hub__grid">
        {HUB_MODES.map((mode) => {
          const copy = HUB_CARD_COPY[mode];
          const Icon = HUB_ICONS[mode];
          return (
            <li key={mode}>
              <button type="button" className="product-upload-hub-card" onClick={() => onSelect(mode)}>
                <span className="product-upload-hub-card__icon" aria-hidden>
                  <Icon size={22} strokeWidth={1.75} />
                </span>
                <span className="product-upload-hub-card__body">
                  <span className="product-upload-hub-card__title">{copy.title}</span>
                  <span className="product-upload-hub-card__desc">{copy.description}</span>
                </span>
              </button>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
