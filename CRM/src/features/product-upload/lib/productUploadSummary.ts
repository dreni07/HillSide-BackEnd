/**
 * Tekste UI të përbashkëta për përmbledhje skedarësh (DRY).
 */

export function summarizeFileNamesForDisplay(files: File[]): {
  fileNamesSummary: string;
  uploadedFileCount: number;
} {
  const names = files
    .map((f) => f.name)
    .slice(0, 3)
    .join(', ');
  const more = files.length > 3 ? ` (+${files.length - 3} të tjerë)` : '';
  return { fileNamesSummary: `${names}${more}`, uploadedFileCount: files.length };
}
