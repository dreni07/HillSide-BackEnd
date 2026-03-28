import { useMemo } from 'react';
import { STUDIO_DOMAIN_ITEMS, STUDIO_SECTION_LABELS } from '../data/studioPalette';
import type { StudioPaletteCategory, StudioPaletteItem } from '../types/studio';

export interface PaletteSection {
  category: StudioPaletteCategory;
  title: string;
  items: StudioPaletteItem[];
}

export function useFilteredPalette(search: string): PaletteSection[] {
  const q = search.trim().toLowerCase();

  return useMemo(() => {
    const filtered = q
      ? STUDIO_DOMAIN_ITEMS.filter(
          (item) =>
            item.label.toLowerCase().includes(q) ||
            (item.hint?.toLowerCase().includes(q) ?? false),
        )
      : STUDIO_DOMAIN_ITEMS;

    const categories: StudioPaletteCategory[] = ['personality', 'restrictions', 'sales', 'qa'];
    return categories.map((category) => ({
      category,
      title: STUDIO_SECTION_LABELS[category],
      items: filtered.filter((i) => i.category === category),
    }));
  }, [q]);
}
