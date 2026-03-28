import {
  BarChart3,
  Box,
  Layers,
  LineChart,
  List,
  UserRound,
  type LucideIcon,
} from 'lucide-react';

const items: {
  id: string;
  icon: LucideIcon;
  label: string;
  active?: boolean;
}[] = [
  { id: 'dash', icon: BarChart3, label: 'Dashboard' },
  { id: 'layers', icon: Layers, label: 'Layers', active: true },
  { id: 'nodes', icon: Box, label: 'Nodes' },
  { id: 'list', icon: List, label: 'List' },
  { id: 'analytics', icon: LineChart, label: 'Analytics' },
];

export function StudioIconRail() {
  return (
    <nav className="studio-icon-rail" aria-label="Studio navigation">
      <div className="studio-icon-rail__top">
        {items.map(({ id, icon: Icon, label, active }) => (
          <button
            key={id}
            type="button"
            className={`studio-rail-btn${active ? ' studio-rail-btn--active' : ''}`}
            aria-label={label}
            aria-current={active ? 'page' : undefined}
          >
            <Icon size={20} strokeWidth={1.75} />
          </button>
        ))}
      </div>
      <div className="studio-icon-rail__bottom">
        <button type="button" className="studio-rail-avatar" aria-label="Account">
          <UserRound size={20} />
        </button>
        <div className="studio-rail-logo" aria-hidden>
          C
        </div>
      </div>
    </nav>
  );
}
