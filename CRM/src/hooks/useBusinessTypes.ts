import { useEffect, useState } from 'react';
import { apiRequest } from '../services/api';
import type { BusinessType } from '../types/api';
import type { GroupedBusinessTypes } from '../types/onboarding';

export function useBusinessTypes() {
  const [types, setTypes] = useState<GroupedBusinessTypes>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    apiRequest<BusinessType[]>('/api/business-types')
      .then((data) => {
        if (cancelled) return;
        const grouped: GroupedBusinessTypes = {};
        for (const bt of data) {
          (grouped[bt.category] ??= []).push(bt);
        }
        setTypes(grouped);
      })
      .catch(() => {
        if (cancelled) return;
        setError('Nuk mund të ngarkoheshin llojet e biznesit. Rifreskoni faqen.');
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => { cancelled = true; };
  }, []);

  return { types, loading, error };
}
