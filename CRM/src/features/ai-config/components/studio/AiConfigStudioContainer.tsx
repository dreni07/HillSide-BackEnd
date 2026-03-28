import { AlertModal } from '../../../../components/ui/AlertModal';
import { LoadingOverlay } from '../../../../components/ui/LoadingOverlay';
import { AiStudioProvider, useAiStudioContext } from '../../context/AiStudioContext';
import { StudioShell } from './StudioShell';

function AiConfigShellOverlays() {
  const { isBootstrapping, isSaving, bootstrapError, reloadFromServer } = useAiStudioContext();

  return (
    <>
      <LoadingOverlay
        open={isBootstrapping || isSaving}
        title={isSaving ? 'Duke ruajtur konfigurimin…' : 'Duke ngarkuar konfigurimin…'}
        description={
          isSaving
            ? 'Po dërgohen të dhënat te serveri. Mos e mbyll faqen.'
            : 'Po lexohen personaliteti, kufizimet, shitësi dhe pyetjet e pritura.'
        }
      />
      <AlertModal
        open={!!bootstrapError}
        variant="error"
        title="Gabim në ngarkim"
        message={bootstrapError ?? ''}
        confirmLabel="Provo përsëri"
        onClose={() => {
          void reloadFromServer();
        }}
      />
    </>
  );
}

export function AiConfigStudioContainer() {
  return (
    <AiStudioProvider>
      <AiConfigShellOverlays />
      <StudioShell />
    </AiStudioProvider>
  );
}
