import { Filter, GripHorizontal, Search } from 'lucide-react';
import { useFilteredPalette, type PaletteSection } from '../../../hooks/useFilteredPalette';
import { useAiStudio } from '../../../hooks/useAiStudio';
import type { StudioPaletteItem } from '../../../types/studio';
import { PaletteCategoryIcon } from './PaletteCategoryIcon';

function LibrarySearch() {
  const { librarySearch, setLibrarySearch } = useAiStudio();
  return (
    <div className="studio-library-search">
      <Search size={16} className="studio-library-search__icon" aria-hidden />
      <input
        type="search"
        className="studio-library-search__input"
        placeholder="Search"
        value={librarySearch}
        onChange={(e) => setLibrarySearch(e.target.value)}
        aria-label="Search components"
      />
      <button type="button" className="studio-icon-btn studio-icon-btn--muted" aria-label="Filter">
        <Filter size={16} />
      </button>
    </div>
  );
}

function LibraryItemCard({ item, selected }: { item: StudioPaletteItem; selected: boolean }) {
  const { selectFromPalette } = useAiStudio();
  return (
    <button
      type="button"
      className={`studio-library-card${selected ? ' studio-library-card--selected' : ''}`}
      onClick={() => selectFromPalette(item.id)}
    >
      <PaletteCategoryIcon category={item.category} />
      <span className="studio-library-card__label">{item.label}</span>
      <GripHorizontal size={14} className="studio-library-card__drag" aria-hidden />
    </button>
  );
}

function LibrarySectionBody({ category }: { category: PaletteSection }) {
  const { selection } = useAiStudio();
  if (category.items.length === 0) return null;

  const sectionClass =
    category.category === 'personality'
      ? 'studio-library-section studio-library-section--personality'
      : category.category === 'restrictions'
        ? 'studio-library-section studio-library-section--restrictions'
        : category.category === 'sales'
          ? 'studio-library-section studio-library-section--sales'
          : 'studio-library-section studio-library-section--qa';

  return (
    <details className={sectionClass} open>
      <summary className="studio-library-section__summary">
        <span
          className={`studio-library-section__dot studio-library-section__dot--${category.category}`}
          aria-hidden
        />
        {category.title}
      </summary>
      <div className="studio-library-section__list">
        {category.items.map((item) => {
          const selected =
            selection?.source === 'palette' && selection.itemId === item.id;
          return <LibraryItemCard key={item.id} item={item} selected={selected} />;
        })}
      </div>
    </details>
  );
}

function LibraryScrollBody() {
  const { librarySearch } = useAiStudio();
  const sections = useFilteredPalette(librarySearch);
  return (
    <div className="studio-library-scroll">
      {sections.map((sec) => (
        <LibrarySectionBody key={sec.category} category={sec} />
      ))}
    </div>
  );
}


export function StudioLibraryPanel() {
  return (
    <aside className="studio-library-panel">
      <LibrarySearch />
      <LibraryScrollBody />
    </aside>
  );
}

StudioLibraryPanel.Search = LibrarySearch;
StudioLibraryPanel.Body = LibraryScrollBody;
