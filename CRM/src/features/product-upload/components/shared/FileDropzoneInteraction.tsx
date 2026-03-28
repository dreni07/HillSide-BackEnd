/**
 * Render props për zonën e tërheqjes: bashkon logjikën drag/drop + input ref,
 * ndërsa pamja mbetet te consuming component (presentational).
 */

import { useCallback, useId, useRef, useState, type ReactNode } from 'react';
import { ProductUploadFileInput } from './ProductUploadFileInput';

export interface FileDropzoneRenderContext {
  isDragging: boolean;
  /** Hap dialogun nativ të skedarëve. */
  openFileDialog: () => void;
  idInput: string;
  idHelp: string;
  /** Kalojini sipërfaqes së dropzones (buton / div). */
  dropHandlers: {
    onDragOver: (e: React.DragEvent) => void;
    onDragLeave: (e: React.DragEvent) => void;
    onDrop: (e: React.DragEvent) => void;
  };
}

export interface FileDropzoneInteractionProps {
  disabled?: boolean;
  accept: string;
  multiple?: boolean;
  inputAriaLabel: string;
  onFilesSelected: (files: FileList) => void;
  /** Pamja merr gjendjen dhe veprimet; inputi renderohet brenda këtij komponenti. */
  children: (ctx: FileDropzoneRenderContext) => ReactNode;
}

export function FileDropzoneInteraction({
  disabled,
  accept,
  multiple = true,
  inputAriaLabel,
  onFilesSelected,
  children,
}: FileDropzoneInteractionProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const reactId = useId();
  const idInput = `pu-file-${reactId}`;
  const idHelp = `pu-file-help-${reactId}`;
  const [isDragging, setIsDragging] = useState(false);

  const openFileDialog = useCallback(() => {
    if (disabled) return;
    inputRef.current?.click();
  }, [disabled]);

  const handleChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const list = e.target.files;
      if (list?.length) onFilesSelected(list);
      e.target.value = '';
    },
    [onFilesSelected],
  );

  const onDragOver = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
      if (!disabled) setIsDragging(true);
    },
    [disabled],
  );

  const onDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setIsDragging(false);
  }, []);

  const onDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault();
      e.stopPropagation();
      setIsDragging(false);
      if (disabled) return;
      const { files } = e.dataTransfer;
      if (files?.length) onFilesSelected(files);
    },
    [disabled, onFilesSelected],
  );

  const ctx: FileDropzoneRenderContext = {
    isDragging,
    openFileDialog,
    idInput,
    idHelp,
    dropHandlers: { onDragOver, onDragLeave, onDrop },
  };

  return (
    <>
      <ProductUploadFileInput
        ref={inputRef}
        id={idInput}
        accept={accept}
        multiple={multiple}
        onChange={handleChange}
        disabled={disabled}
        aria-label={inputAriaLabel}
      />
      {children(ctx)}
    </>
  );
}
