/**
 * Container: lidh FileDropzoneInteraction (render props) me kontekstin dhe pamjet presentational.
 */

import { acceptForKind } from '../../constants/productUpload.constants';
import { useProductUpload } from '../../hooks/useProductUpload';
import type { ProductFileKind } from '../../types/productUpload.types';
import { ProductFileDropzoneSurfaceView } from '../presentational/ProductFileDropzoneSurfaceView';
import { ProductFileToolbarView } from '../presentational/ProductFileToolbarView';
import { FileDropzoneInteraction } from '../shared/FileDropzoneInteraction';

const DOCUMENT_HINT = 'PDF, CSV, XLSX — deri në disa skedarë njëkohësisht.';
const IMAGE_HINT = 'PNG, JPG, WebP, HEIC — një ose më shumë foto.';

const TITLES: Record<ProductFileKind, string> = {
  document: 'Ngarko PDF ose fletëllogaritje',
  image: 'Ngarko imazh për OCR',
};

export interface ProductFileUploadPanelContainerProps {
  kind: ProductFileKind;
}

export function ProductFileUploadPanelContainer({ kind }: ProductFileUploadPanelContainerProps) {
  const { processUploadedFiles, isProcessing } = useProductUpload();
  const accept = acceptForKind(kind);
  const hint = kind === 'document' ? DOCUMENT_HINT : IMAGE_HINT;
  const title = TITLES[kind];

  return (
    <FileDropzoneInteraction
      disabled={isProcessing}
      accept={accept}
      multiple
      inputAriaLabel={title}
      onFilesSelected={(files) => {
        void processUploadedFiles(files, kind);
      }}
    >
      {(ctx) => (
        <div className="product-upload-file-panel">
          <ProductFileToolbarView
            hint={hint}
            disabled={isProcessing}
            onPick={ctx.openFileDialog}
          />
          <ProductFileDropzoneSurfaceView
            title={title}
            isDragging={ctx.isDragging}
            disabled={isProcessing}
            helpId={ctx.idHelp}
            onClick={ctx.openFileDialog}
            onDragOver={ctx.dropHandlers.onDragOver}
            onDragLeave={ctx.dropHandlers.onDragLeave}
            onDrop={ctx.dropHandlers.onDrop}
          />
        </div>
      )}
    </FileDropzoneInteraction>
  );
}
