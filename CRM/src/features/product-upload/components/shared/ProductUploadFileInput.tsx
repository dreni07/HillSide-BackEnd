/**
 * Input i fshehtë për skedarë — forwardRef për fokus / klik programatik nga prindi.
 */

import { forwardRef, type InputHTMLAttributes } from 'react';

export type ProductUploadFileInputProps = Omit<
  InputHTMLAttributes<HTMLInputElement>,
  'type' | 'ref'
> & {
  /** Klasa e fshehtëjes vizuale (default nga CSS global). */
  visuallyHiddenClassName?: string;
};

export const ProductUploadFileInput = forwardRef<HTMLInputElement, ProductUploadFileInputProps>(
  function ProductUploadFileInput({ visuallyHiddenClassName, className, ...rest }, ref) {
    return (
      <input
        ref={ref}
        type="file"
        className={className ?? visuallyHiddenClassName ?? 'product-upload-file-input'}
        {...rest}
      />
    );
  },
);

ProductUploadFileInput.displayName = 'ProductUploadFileInput';
