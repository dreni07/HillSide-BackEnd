/**
 * Container: gjendja e formës (vlera, touched, submitting) + validim + thirrje `commitManualProduct`.
 */

import { useCallback, useEffect, useLayoutEffect, useRef, useState, type FormEvent } from 'react';
import { EMPTY_MANUAL_FORM_VALUES } from '../../lib/manualFormDefaults';
import { normalizeManualProductPayload, validateManualProductForm } from '../../lib/validateManualProduct';
import { useProductUpload } from '../../hooks/useProductUpload';
import type { ProductManualFormValues } from '../../types/productUpload.types';
import { ProductManualFormFieldsView } from '../presentational/ProductManualFormFieldsView';
import { ProductManualSlideModalFrameView } from '../presentational/ProductManualSlideModalFrameView';

export function ProductManualFormModalContainer() {
  const { manualModalOpen, closeManualModal, commitManualProduct } = useProductUpload();
  const [values, setValues] = useState<ProductManualFormValues>(EMPTY_MANUAL_FORM_VALUES);
  const [touched, setTouched] = useState<Partial<Record<keyof ProductManualFormValues, boolean>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const nameFieldRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (!manualModalOpen) {
      setValues(EMPTY_MANUAL_FORM_VALUES);
      setTouched({});
      setIsSubmitting(false);
    }
  }, [manualModalOpen]);

  useLayoutEffect(() => {
    if (!manualModalOpen) return;
    const id = window.setTimeout(() => nameFieldRef.current?.focus(), 90);
    return () => window.clearTimeout(id);
  }, [manualModalOpen]);

  const errors = validateManualProductForm(values);

  const handleChange = useCallback((field: keyof ProductManualFormValues, value: string) => {
    setValues((prev) => ({ ...prev, [field]: value }));
  }, []);

  const handleBlur = useCallback((field: keyof ProductManualFormValues) => {
    setTouched((prev) => ({ ...prev, [field]: true }));
  }, []);

  const handleSubmit = useCallback(
    async (e: FormEvent<HTMLFormElement>) => {
      e.preventDefault();
      setTouched((prev) => ({
        ...prev,
        name: true,
        price: true,
        stock: true,
      }));
      const nextErrors = validateManualProductForm(values);
      if (Object.keys(nextErrors).length > 0) return;

      setIsSubmitting(true);
      try {
        const payload = normalizeManualProductPayload(values);
        await commitManualProduct(payload);
      } finally {
        setIsSubmitting(false);
      }
    },
    [values, commitManualProduct],
  );

  return (
    <ProductManualSlideModalFrameView
      open={manualModalOpen}
      title="Produkt i ri (manual)"
      subtitle="Plotësoni fushat; të dhënat ruhen në server për biznesin tuaj."
      onClose={closeManualModal}
    >
      <ProductManualFormFieldsView
        ref={nameFieldRef}
        values={values}
        errors={errors}
        touched={touched}
        onChange={handleChange}
        onBlur={handleBlur}
        onSubmit={handleSubmit}
        disabled={isSubmitting}
        footer={
          <div className="product-upload-slide-footer">
            <button
              type="button"
              className="studio-link-btn"
              onClick={closeManualModal}
              disabled={isSubmitting}
            >
              Anulo
            </button>
            <button
              type="submit"
              className="studio-btn studio-btn--primary"
              disabled={isSubmitting}
            >
              {isSubmitting ? 'Duke ruajtur…' : 'Ruaj produktin'}
            </button>
          </div>
        }
      />
    </ProductManualSlideModalFrameView>
  );
}
