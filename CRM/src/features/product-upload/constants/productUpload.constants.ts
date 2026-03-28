/**
 * Konstante të përbashkëta — accept strings, kohëmatës, kopje UI.
 */

import type {
  ProductFileKind,
  ProductUploadHubMode,
  ProductUploadProcessOverlay,
  ProductUploadView,
} from '../types/productUpload.types';

/** MIME / prapashtesa të përputhshme me validimin Laravel (pdf, csv, txt, xlsx, ods). */
export const DOCUMENT_ACCEPT =
  '.pdf,.csv,.txt,.xlsx,.ods,application/pdf,text/csv,text/plain,application/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.oasis.opendocument.spreadsheet';

/** Imazhe për OCR (përputhet me StoreProductImageRequest). */
export const IMAGE_ACCEPT =
  'image/png,image/jpeg,image/jpg,image/webp,image/gif,image/bmp,image/tiff,.png,.jpg,.jpeg,.webp,.gif,.bmp,.tif,.tiff,.heic';

/** Titulli i faqes në top bar. */
export const PRODUCT_UPLOAD_PAGE_TITLE = 'Ngarkimi i produkteve';

/** Nëntituj sipas pamjes. */
export const PRODUCT_UPLOAD_SUBTITLES: Record<ProductUploadView, string> = {
  hub: 'Shtoni produkte për asistentin e shitjeve me AI',
  document: 'Dokumente strukturuese (PDF, Excel, CSV)',
  image: 'Imazhe për OCR',
};

/** Tekstet e kartave të hub-it (ikona caktohen në view). */
export const HUB_CARD_COPY: Record<
  ProductUploadHubMode,
  { title: string; description: string }
> = {
  document: {
    title: 'Bashkangjit dokument',
    description: 'PDF, Excel ose CSV — ekstraktim i strukturuar për produktet.',
  },
  image: {
    title: 'Bashkangjit imazh',
    description: 'Foto katalogu ose skan — OCR për të nxjerrë të dhënat.',
  },
  manual: {
    title: 'Plotëso manualisht',
    description: 'Hyni emrin, çmimin, SKU, kategorinë dhe përshkrimin direkt.',
  },
};

export function acceptForKind(kind: ProductFileKind): string {
  return kind === 'document' ? DOCUMENT_ACCEPT : IMAGE_ACCEPT;
}

/** Tekstet e LoadingOverlay sipas llojit të skedarit. */
export function processOverlayForKind(kind: ProductFileKind): ProductUploadProcessOverlay {
  if (kind === 'document') {
    return {
      title: 'Duke ekstraktuar dokumentin…',
      description:
        'Po lexohet struktura e PDF / fletëllogaritjes. Hapi i ardhshëm: modeli i gjuhës (Groq).',
    };
  }
  return {
    title: 'Duke përpunuar imazhin (OCR)…',
    description:
      'Po ekzekutohet Tesseract OCR dhe strukturimi me AI (Groq) në server.',
  };
}
