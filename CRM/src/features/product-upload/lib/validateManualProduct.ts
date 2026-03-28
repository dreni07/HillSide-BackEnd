/**
 * Validim i pastër për formën manuale — i ripërdorshëm nga testet dhe UI.
 */

import type {
  ManualProductPayload,
  ProductManualFieldErrors,
  ProductManualFormValues,
} from '../types/productUpload.types';

export function validateManualProductForm(values: ProductManualFormValues): ProductManualFieldErrors {
  const e: ProductManualFieldErrors = {};
  const name = values.name.trim();
  if (!name) e.name = 'Emri i produktit është i detyrueshëm.';

  if (values.price.trim()) {
    const n = Number(values.price.replace(',', '.'));
    if (Number.isNaN(n) || n < 0) e.price = 'Shkruani një çmim të vlefshëm (numër ≥ 0).';
  }

  if (values.stock.trim()) {
    const s = Number(values.stock);
    if (!Number.isInteger(s) || s < 0) e.stock = 'Stoku duhet të jetë numër i plotë ≥ 0.';
  }

  return e;
}

/** Normalizon vlerat pas validimit të suksesshëm. */
export function normalizeManualProductPayload(values: ProductManualFormValues): ManualProductPayload {
  return {
    name: values.name.trim(),
    description: values.description.trim(),
    price: values.price.trim(),
    sku: values.sku.trim(),
    category: values.category.trim(),
    stock: values.stock.trim(),
    unit: values.unit.trim(),
    tags: values.tags.trim(),
  };
}
