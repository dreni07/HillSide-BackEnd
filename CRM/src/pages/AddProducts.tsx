import { useMemo, useState } from 'react';
import * as XLSX from 'xlsx';
import type { ChangeEvent, FormEvent } from 'react';

type Product = {
  name: string;
  description: string;
  price: number;
  category?: string | null;
};

function normalizeHeader(header: string): string {
  return header
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ');
}

function parsePriceLoose(raw: unknown): number | null {
  const s = String(raw ?? '').trim();
  if (!s) return null;

  // Removes currency symbols/spaces; keeps digits and separators.
  let t = s.replace(/[^0-9.,-]/g, '');
  const lastComma = t.lastIndexOf(',');
  const lastDot = t.lastIndexOf('.');

  // If both exist, decide which one is decimal separator based on the last occurrence.
  if (lastComma !== -1 && lastDot !== -1) {
    if (lastComma > lastDot) {
      // Example: 1.234,56 -> 1234.56
      t = t.replace(/\./g, '').replace(',', '.');
    } else {
      // Example: 1,234.56 -> 1234.56
      t = t.replace(/,/g, '');
    }
  } else if (lastComma !== -1) {
    t = t.replace(',', '.');
  }

  const n = Number(t);
  return Number.isFinite(n) ? n : null;
}

function extractProductFromRow(row: Record<string, unknown>): { product: Product | null; invalid: boolean } {
  const keys = Object.keys(row);

  let nameRaw: unknown = '';
  let descriptionRaw: unknown = '';
  let priceRaw: unknown = '';
  let categoryRaw: unknown = '';

  for (const k of keys) {
    const nk = normalizeHeader(k);

    const isName =
      nk.includes('emri') || nk === 'name' || nk.includes('produkt') || nk.includes('product');
    const isDescription = nk.includes('pershkrimi') || nk.includes('description');
    const isPrice = nk.includes('cmimi') || nk.includes('cmim') || nk.includes('price');
    const isCategory = nk.includes('kategoria') || nk.includes('kategori') || nk.includes('category');

    if (isName) nameRaw = row[k];
    else if (isDescription) descriptionRaw = row[k];
    else if (isPrice) priceRaw = row[k];
    else if (isCategory) categoryRaw = row[k];
  }

  const name = String(nameRaw ?? '').trim();
  const description = String(descriptionRaw ?? '').trim();
  const price = parsePriceLoose(priceRaw);
  const category = String(categoryRaw ?? '').trim();

  const invalid = !name || !description || price === null;
  if (invalid) return { product: null, invalid: true };

  return {
    product: {
      name,
      description,
      price,
      category: category ? category : null,
    },
    invalid: false,
  };
}

function buildAiPayload(products: Product[]) {
  return {
    products: products.map((p) => ({
      name: p.name,
      description: p.description,
      price: p.price,
      ...(p.category ? { category: p.category } : {}),
    })),
  };
}

export function AddProducts() {
  const [tab, setTab] = useState<'manual' | 'upload'>('manual');
  const [products, setProducts] = useState<Product[]>([]);

  // Manual inputs
  const [manualName, setManualName] = useState('');
  const [manualDescription, setManualDescription] = useState('');
  const [manualPrice, setManualPrice] = useState<string>('');
  const [manualCategory, setManualCategory] = useState<string>('');

  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loadingImport, setLoadingImport] = useState(false);
  const [importWarnings, setImportWarnings] = useState<string>('');
  const [copied, setCopied] = useState(false);

  const aiPayload = useMemo(() => buildAiPayload(products), [products]);
  const aiPayloadText = useMemo(() => JSON.stringify(aiPayload, null, 2), [aiPayload]);

  function resetMessages() {
    setError('');
    setSuccess('');
    setImportWarnings('');
    setCopied(false);
  }

  function handleAddManual(e: FormEvent) {
    e.preventDefault();
    resetMessages();

    const price = parsePriceLoose(manualPrice);
    if (!manualName.trim()) return setError('Emri i produktit është i detyrueshëm.');
    if (!manualDescription.trim()) return setError('Përshkrimi është i detyrueshëm.');
    if (price === null) return setError('Çmimi duhet të jetë numër i vlefshëm.');

    const next: Product = {
      name: manualName.trim(),
      description: manualDescription.trim(),
      price,
      category: manualCategory.trim() ? manualCategory.trim() : null,
    };

    setProducts((prev) => [...prev, next]);
    setSuccess('U shtua produkt i ri.');
    setManualName('');
    setManualDescription('');
    setManualPrice('');
    setManualCategory('');
  }

  async function handleImportFromFile(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;
    resetMessages();
    setLoadingImport(true);

    try {
      const ext = file.name.split('.').pop()?.toLowerCase();
      let workbook: XLSX.WorkBook;

      if (ext === 'csv') {
        const text = await file.text();
        workbook = XLSX.read(text, { type: 'string', raw: false });
      } else if (ext === 'xlsx' || ext === 'xls') {
        const buf = await file.arrayBuffer();
        workbook = XLSX.read(new Uint8Array(buf), { type: 'array' });
      } else {
        setError('Lejohet vetëm Excel (XLSX/XLS) ose CSV.');
        return;
      }

      const firstSheet = workbook.SheetNames[0];
      if (!firstSheet) {
        setError('Nuk u gjet asnjë fletë në dokument.');
        return;
      }

      const sheet = workbook.Sheets[firstSheet];
      const rows = XLSX.utils.sheet_to_json<Record<string, unknown>>(sheet, { defval: '' });
      if (!Array.isArray(rows) || rows.length === 0) {
        setError('Dokumenti nuk përmban rreshta të dhënash.');
        return;
      }

      const allKeys = new Set<string>();
      for (const r of rows) {
        for (const k of Object.keys(r)) allKeys.add(k);
      }

      let hasName = false;
      let hasDescription = false;
      let hasPrice = false;

      for (const k of allKeys) {
        const nk = normalizeHeader(k);
        if (nk.includes('emri') || nk === 'name' || nk.includes('produkt') || nk.includes('product')) hasName = true;
        if (nk.includes('pershkrimi') || nk.includes('description')) hasDescription = true;
        if (nk.includes('cmimi') || nk.includes('cmim') || nk.includes('price')) hasPrice = true;
      }

      if (!hasName || !hasDescription || !hasPrice) {
        setError(
          'Kolonat e kërkuara mungojnë. Duhet të keni: Emri i produktit, Përshkrimi dhe Çmimi (Kategoria është opsionale).',
        );
        return;
      }

      let invalidCount = 0;
      const parsed: Product[] = [];

      rows.forEach((row) => {
        const extracted = extractProductFromRow(row);
        if (extracted.invalid || !extracted.product) invalidCount += 1;
        else parsed.push(extracted.product);
      });

      if (parsed.length === 0) {
        setError('Nuk u gjet asnjë produkt i vlefshëm në file.');
        return;
      }

      setProducts(parsed);
      setSuccess(`Import u krye. U shtuan ${parsed.length} produkte.`);
      if (invalidCount > 0) {
        setImportWarnings(`U injoruan ${invalidCount} rreshta pa të dhëna të plota.`);
      }
      setCopied(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gabim gjatë importit të file-it.');
    } finally {
      setLoadingImport(false);
    }
  }

  async function handleCopyAiJson() {
    resetMessages();
    try {
      await navigator.clipboard.writeText(aiPayloadText);
      setCopied(true);
    } catch {
      setError('Nuk arrita të kopjoj në clipboard. Provo manualisht selektim.');
    }
  }

  function handleRemoveProduct(index: number) {
    setProducts((prev) => prev.filter((_, i) => i !== index));
    setSuccess('Produkt u hoq nga lista.');
  }

  const hasProducts = products.length > 0;

  return (
    <div className="page-add-products">
      <h1>Shto Produkte</h1>
      <p className="page-add-products-subtitle">
        Përgatitni produktet që AI do t’i përdorë në përgjigje. Mund t’i shtoni manualisht ose nga një file Excel/CSV (me preview).
      </p>

      {error && (
        <div className="products-error" role="alert">
          {error}
        </div>
      )}
      {success && <div className="form-success">{success}</div>}
      {importWarnings && <div className="page-error">{importWarnings}</div>}

      <div className="products-tabs" role="tablist" aria-label="Mënyrat e shtimit">
        <button
          type="button"
          className={`btn-secondary products-tab-button ${tab === 'manual' ? 'products-tab-button--active' : ''}`}
          onClick={() => setTab('manual')}
        >
          Manualisht
        </button>
        <button
          type="button"
          className={`btn-secondary products-tab-button ${tab === 'upload' ? 'products-tab-button--active' : ''}`}
          onClick={() => setTab('upload')}
        >
          Upload (Excel/CSV)
        </button>
      </div>

      {tab === 'manual' && (
        <section className="settings-section">
          <h2>Manualisht</h2>
          <p className="settings-hint">Plotëson fushat dhe shto produktin në listë.</p>
          <form onSubmit={handleAddManual} className="settings-form">
            <label>
              Emri i produktit<span className="required">*</span>
              <input
                type="text"
                value={manualName}
                onChange={(e) => setManualName(e.target.value)}
                placeholder="p.sh. Kafe Arabica 1kg"
              />
            </label>
            <label>
              Përshkrimi<span className="required">*</span>
              <textarea
                value={manualDescription}
                onChange={(e) => setManualDescription(e.target.value)}
                placeholder="Përshkruani produktin (çfarë ka, për kë, etj.)"
                rows={5}
                className="settings-textarea"
              />
            </label>
            <label>
              Çmimi<span className="required">*</span>
              <input
                type="text"
                value={manualPrice}
                onChange={(e) => setManualPrice(e.target.value)}
                placeholder="p.sh. 12.50"
              />
            </label>
            <label>
              Kategoria (opsionale)
              <input
                type="text"
                value={manualCategory}
                onChange={(e) => setManualCategory(e.target.value)}
                placeholder="p.sh. Kafe, Ëmbëlsira..."
              />
            </label>

            <div className="form-actions">
              <button type="submit" className="btn-primary">
                Shto produkt
              </button>
            </div>
          </form>
        </section>
      )}

      {tab === 'upload' && (
        <section className="settings-section">
          <h2>Upload me Excel ose CSV</h2>
          <p className="settings-hint">
            File duhet të ketë një rresht header. Kolonat e nevojshme: <b>Emri</b>, <b>Përshkrimi</b>, <b>Çmimi</b> (Kategoria është opsionale).
          </p>

          <label className="products-upload-box">
            <span className="products-upload-box-title">Zgjidh file</span>
            <span className="products-upload-box-hint">Excel (XLSX/XLS) ose CSV</span>
            <input
              type="file"
              accept=".csv,.xlsx,.xls"
              onChange={handleImportFromFile}
              className="products-upload-input"
              disabled={loadingImport}
            />
          </label>

          {loadingImport && <div className="page-loading">Duke importuar…</div>}

          {hasProducts ? (
            <p className="page-contacts-hint" style={{ marginTop: '1rem' }}>
              U lexuan {products.length} produkte. Preview-i shfaqet më poshtë.
            </p>
          ) : (
            <div className="automation-empty" style={{ marginTop: '1rem' }}>
              <p>Nuk ka produkte për momentin. Ngarko një file për të parë preview.</p>
            </div>
          )}
        </section>
      )}

      {hasProducts && (
        <section className="settings-section">
          <h2>Lista e produkteve</h2>
          <p className="settings-hint">Të dhënat që AI do t’i përdorë (front-end vetëm).</p>
          <table className="table-automation">
            <thead>
              <tr>
                <th>Emri</th>
                <th>Përshkrimi</th>
                <th>Çmimi</th>
                <th>Kategoria</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {products.map((p, idx) => (
                <tr key={`${p.name}-${idx}`}>
                  <td className="td-value">{p.name}</td>
                  <td className="td-response">{p.description}</td>
                  <td className="td-value">{p.price.toFixed(2)}</td>
                  <td className="td-value">{p.category ?? '-'}</td>
                  <td>
                    <button type="button" className="btn-danger btn-sm" onClick={() => handleRemoveProduct(idx)}>
                      Fshi
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
      )}

      {hasProducts && (
        <section className="settings-section">
          <h2>Struktura për AI (JSON)</h2>
          <p className="settings-hint">
            Kopjo këtë JSON për ta përdorur nga AI system (front-end vetëm; pa ndryshuar backend).
          </p>

          <div className="form-actions" style={{ justifyContent: 'space-between' }}>
            <button type="button" className="btn-primary" onClick={handleCopyAiJson}>
              {copied ? 'U kopjua!' : 'Kopjo JSON'}
            </button>
          </div>

          <div className="products-json" aria-label="JSON preview">
            <pre>{aiPayloadText}</pre>
          </div>
        </section>
      )}
    </div>
  );
}

