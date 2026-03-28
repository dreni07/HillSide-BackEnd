/**
 * Ngarkon `business_id` nga API para se të aktivizohet konteksti i ngarkimit të produkteve.
 */

import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { apiRequest, type Business } from '../../../services/api';
import { ProductUploadProvider } from '../context/ProductUploadContext';

interface ProductUploadBusinessGateProps {
  children: ReactNode;
}

export function ProductUploadBusinessGate({ children }: ProductUploadBusinessGateProps) {
  const [businessId, setBusinessId] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const business = await apiRequest<Business>('/api/business/me');
      setBusinessId(String(business.id));
    } catch (e) {
      const msg =
        e instanceof Error ? e.message : 'Nuk u lexua biznesi. Sigurohuni që keni krijuar profilin e biznesit.';
      setError(msg);
      setBusinessId(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  if (loading) {
    return <div className="auth-loading">Duke ngarkuar biznesin…</div>;
  }

  if (error !== null || businessId === null) {
    return (
      <div className="ai-config-studio-root product-upload-root product-upload-gate">
        <div className="product-upload-gate-card">
          <h2 className="product-upload-gate-title">Nuk mund të vazhdohet</h2>
          <p className="product-upload-gate-message">{error ?? 'Biznesi nuk u gjet.'}</p>
          <button type="button" className="studio-btn studio-btn--primary" onClick={() => void load()}>
            Provo përsëri
          </button>
        </div>
      </div>
    );
  }

  return <ProductUploadProvider businessId={businessId}>{children}</ProductUploadProvider>;
}
