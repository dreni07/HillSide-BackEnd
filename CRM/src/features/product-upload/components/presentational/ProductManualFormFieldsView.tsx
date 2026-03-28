/**
 * Presentational: fushat e formës manuale + footer (submit), të kontrolluara nga prindi.
 * forwardRef drejtohet te inputi i emrit për fokus kur hapet modal-i.
 */

import { forwardRef, type FormEventHandler, type ReactNode } from 'react';
import type { ProductManualFieldErrors, ProductManualFormValues } from '../../types/productUpload.types';

export interface ProductManualFormFieldsViewProps {
  values: ProductManualFormValues;
  errors: ProductManualFieldErrors;
  touched: Partial<Record<keyof ProductManualFormValues, boolean>>;
  onChange: (field: keyof ProductManualFormValues, value: string) => void;
  onBlur: (field: keyof ProductManualFormValues) => void;
  onSubmit: FormEventHandler<HTMLFormElement>;
  footer: ReactNode;
  /** Çaktivizon butonat gjatë ruajtjes. */
  disabled?: boolean;
}

function showFieldError(
  field: keyof ProductManualFormValues,
  touched: Partial<Record<keyof ProductManualFormValues, boolean>>,
  errors: ProductManualFieldErrors,
) {
  return touched[field] ? errors[field] : undefined;
}

export const ProductManualFormFieldsView = forwardRef<HTMLInputElement, ProductManualFormFieldsViewProps>(
  function ProductManualFormFieldsView(
    { values, errors, touched, onChange, onBlur, onSubmit, footer, disabled },
    ref,
  ) {
    const errName = showFieldError('name', touched, errors);
    const errPrice = showFieldError('price', touched, errors);
    const errStock = showFieldError('stock', touched, errors);

    return (
      <form className="product-upload-slide-form" onSubmit={onSubmit} noValidate>
        <div className="product-upload-field">
          <label htmlFor="pu-name">
            Emri i produktit <span className="product-upload-req">*</span>
          </label>
          <input
            ref={ref}
            id="pu-name"
            name="name"
            type="text"
            autoComplete="off"
            disabled={disabled}
            value={values.name}
            onChange={(ev) => onChange('name', ev.target.value)}
            onBlur={() => onBlur('name')}
            className={errName ? 'product-upload-input product-upload-input--error' : 'product-upload-input'}
            aria-invalid={!!errName}
            aria-describedby={errName ? 'pu-name-err' : undefined}
          />
          {errName ? (
            <p id="pu-name-err" className="product-upload-field-error" role="alert">
              {errName}
            </p>
          ) : null}
        </div>

        <div className="product-upload-field-row">
          <div className="product-upload-field">
            <label htmlFor="pu-price">Çmimi</label>
            <input
              id="pu-price"
              name="price"
              type="text"
              inputMode="decimal"
              placeholder="p.sh. 12.99"
              disabled={disabled}
              value={values.price}
              onChange={(ev) => onChange('price', ev.target.value)}
              onBlur={() => onBlur('price')}
              className={errPrice ? 'product-upload-input product-upload-input--error' : 'product-upload-input'}
              aria-invalid={!!errPrice}
              aria-describedby={errPrice ? 'pu-price-err' : undefined}
            />
            {errPrice ? (
              <p id="pu-price-err" className="product-upload-field-error" role="alert">
                {errPrice}
              </p>
            ) : null}
          </div>
          <div className="product-upload-field">
            <label htmlFor="pu-unit">Njësia</label>
            <input
              id="pu-unit"
              name="unit"
              type="text"
              placeholder="p.sh. copë, kg"
              disabled={disabled}
              value={values.unit}
              onChange={(ev) => onChange('unit', ev.target.value)}
              className="product-upload-input"
            />
          </div>
        </div>

        <div className="product-upload-field-row">
          <div className="product-upload-field">
            <label htmlFor="pu-sku">SKU</label>
            <input
              id="pu-sku"
              name="sku"
              type="text"
              disabled={disabled}
              value={values.sku}
              onChange={(ev) => onChange('sku', ev.target.value)}
              className="product-upload-input"
            />
          </div>
          <div className="product-upload-field">
            <label htmlFor="pu-stock">Stoku</label>
            <input
              id="pu-stock"
              name="stock"
              type="text"
              inputMode="numeric"
              placeholder="0"
              disabled={disabled}
              value={values.stock}
              onChange={(ev) => onChange('stock', ev.target.value)}
              onBlur={() => onBlur('stock')}
              className={errStock ? 'product-upload-input product-upload-input--error' : 'product-upload-input'}
              aria-invalid={!!errStock}
              aria-describedby={errStock ? 'pu-stock-err' : undefined}
            />
            {errStock ? (
              <p id="pu-stock-err" className="product-upload-field-error" role="alert">
                {errStock}
              </p>
            ) : null}
          </div>
        </div>

        <div className="product-upload-field">
          <label htmlFor="pu-category">Kategoria</label>
          <input
            id="pu-category"
            name="category"
            type="text"
            list="pu-category-suggestions"
            placeholder="p.sh. Pije, Elektronikë"
            disabled={disabled}
            value={values.category}
            onChange={(ev) => onChange('category', ev.target.value)}
            className="product-upload-input"
          />
          <datalist id="pu-category-suggestions">
            <option value="Ushqim dhe pije" />
            <option value="Veshje" />
            <option value="Elektronikë" />
            <option value="Shtëpi dhe kopsht" />
            <option value="Shërbime" />
          </datalist>
        </div>

        <div className="product-upload-field">
          <label htmlFor="pu-desc">Përshkrimi</label>
          <textarea
            id="pu-desc"
            name="description"
            rows={4}
            disabled={disabled}
            value={values.description}
            onChange={(ev) => onChange('description', ev.target.value)}
            className="product-upload-textarea"
          />
        </div>

        <div className="product-upload-field">
          <label htmlFor="pu-tags">Etiketa (të ndara me presje)</label>
          <input
            id="pu-tags"
            name="tags"
            type="text"
            placeholder="bio, import, sezon"
            disabled={disabled}
            value={values.tags}
            onChange={(ev) => onChange('tags', ev.target.value)}
            className="product-upload-input"
          />
        </div>

        {footer}
      </form>
    );
  },
);

ProductManualFormFieldsView.displayName = 'ProductManualFormFieldsView';
