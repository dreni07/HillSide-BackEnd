/**
 * Faqja e ngarkimit të produkteve: gate për biznesin + konteksti + UI studio.
 */

import { AlertModal } from '../../../components/ui/AlertModal';
import { LoadingOverlay } from '../../../components/ui/LoadingOverlay';
import { ProductUploadBusinessGate } from '../components/ProductUploadBusinessGate';
import { ProductFileUploadPanelContainer } from '../components/containers/ProductFileUploadPanelContainer';
import { ProductManualFormModalContainer } from '../components/containers/ProductManualFormModalContainer';
import { ProductUploadHubView } from '../components/presentational/ProductUploadHubView';
import { ProductUploadTopBarView } from '../components/presentational/ProductUploadTopBarView';
import { PRODUCT_UPLOAD_PAGE_TITLE, PRODUCT_UPLOAD_SUBTITLES } from '../constants/productUpload.constants';
import { useProductUpload } from '../hooks/useProductUpload';

function ProductUploadPageContent() {
  const {
    view,
    goBack,
    selectHubMode,
    isProcessing,
    processOverlay,
    alert,
    dismissAlert,
  } = useProductUpload();

  return (
    <div className="ai-config-studio-root product-upload-root">
      <ProductUploadTopBarView
        title={PRODUCT_UPLOAD_PAGE_TITLE}
        subtitle={PRODUCT_UPLOAD_SUBTITLES[view]}
        onBack={goBack}
      />

      <main className="product-upload-main">
        {view === 'hub' ? <ProductUploadHubView onSelect={selectHubMode} /> : null}
        {view === 'document' ? <ProductFileUploadPanelContainer kind="document" /> : null}
        {view === 'image' ? <ProductFileUploadPanelContainer kind="image" /> : null}
      </main>

      <ProductManualFormModalContainer />

      <LoadingOverlay
        open={isProcessing}
        title={processOverlay.title}
        description={processOverlay.description}
      />

      <AlertModal
        open={!!alert}
        onClose={dismissAlert}
        variant={alert?.variant ?? 'info'}
        title={alert?.title ?? ''}
        message={alert?.message ?? ''}
      />
    </div>
  );
}

export function ProductUploadPage() {
  return (
    <ProductUploadBusinessGate>
      <ProductUploadPageContent />
    </ProductUploadBusinessGate>
  );
}
