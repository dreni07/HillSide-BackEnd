/**
 * Kontrolle klienti për skedarë para dërgimit në API.
 */

/** Kthen mesazh gabimi nëse ka skedarë bosh; përndryshe null. */
export function getEmptyFilesErrorMessage(files: File[]): string | null {
  const empty = files.filter((f) => f.size === 0);
  if (empty.length === 0) return null;
  const names = empty.map((f) => f.name).join(', ');
  return `Një ose më shumë skedarë janë bosh (0 byte): ${names}. Zgjidhni një skedar tjetër.`;
}
