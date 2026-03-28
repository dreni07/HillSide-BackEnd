import type { StudioPaletteCategory } from '../../../types/studio';

export function PaletteCategoryIcon({ category }: { category: StudioPaletteCategory }) {
  if (category === 'personality') {
    return <span className="palette-cat-icon palette-cat-icon--circle" aria-hidden />;
  }
  if (category === 'restrictions') {
    return <span className="palette-cat-icon palette-cat-icon--triangle" aria-hidden />;
  }
  if (category === 'sales') {
    return <span className="palette-cat-icon palette-cat-icon--square" aria-hidden />;
  }
  return <span className="palette-cat-icon palette-cat-icon--diamond" aria-hidden />;
}
