import { useCallback, useState } from 'react';
import { AlertModal } from '../../../../../components/ui/AlertModal';
import { useAiStudio } from '../../../hooks/useAiStudio';
import { StudioTopBarView } from './StudioTopBar';

export function StudioTopBarConnected() {
  const {
    orchestrationTitle,
    orchestrationSubtitle,
    isBootstrapping,
    isSaving,
    saveToServer,
    bootstrapError,
  } = useAiStudio();

  const [saveModal, setSaveModal] = useState<{
    variant: 'success' | 'error';
    message: string;
  } | null>(null);

  const handleSave = useCallback(async () => {
    if (isBootstrapping || isSaving || bootstrapError) return;
    const res = await saveToServer();
    setSaveModal({
      variant: res.ok ? 'success' : 'error',
      message: res.message,
    });
  }, [bootstrapError, isBootstrapping, isSaving, saveToServer]);

  return (
    <>
      <StudioTopBarView
        title={orchestrationTitle}
        subtitle={orchestrationSubtitle}
        onBack={() => window.history.back()}
        onSave={handleSave}
        saveDisabled={isBootstrapping || isSaving || !!bootstrapError}
      />
      <AlertModal
        open={!!saveModal}
        onClose={() => setSaveModal(null)}
        variant={saveModal?.variant ?? 'info'}
        title={saveModal?.variant === 'success' ? 'U ruajt' : 'Ruajtja dështoi'}
        message={saveModal?.message ?? ''}
        confirmLabel={saveModal?.variant === 'success' ? 'Në rregull' : 'Kuptova'}
      />
    </>
  );
}
