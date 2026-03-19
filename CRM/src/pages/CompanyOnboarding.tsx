import { FormEvent, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiRequest, type Business } from '../services/api';

type CompanyOnboardingDraft = {
  companyName: string;
  description: string;
  industry: 'ecommerce' | 'service' | '';
  targetAudience: string;
  location: string;
  language: string;
};

const DRAFT_STORAGE_KEY = 'companyOnboardingDraft';

function readDraftFromSessionStorage(): CompanyOnboardingDraft | null {
  try {
    const raw = sessionStorage.getItem(DRAFT_STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Partial<CompanyOnboardingDraft>;
    return {
      companyName: typeof parsed.companyName === 'string' ? parsed.companyName : '',
      description: typeof parsed.description === 'string' ? parsed.description : '',
      industry: parsed.industry === 'ecommerce' || parsed.industry === 'service' ? parsed.industry : '',
      targetAudience: typeof parsed.targetAudience === 'string' ? parsed.targetAudience : '',
      location: typeof parsed.location === 'string' ? parsed.location : '',
      language: typeof parsed.language === 'string' ? parsed.language : '',
    };
  } catch {
    return null;
  }
}

export function CompanyOnboarding() {
  const navigate = useNavigate();

  const draft = readDraftFromSessionStorage();

  const [companyName, setCompanyName] = useState(draft?.companyName ?? '');
  const [description, setDescription] = useState(draft?.description ?? '');
  const [industry, setIndustry] = useState<'ecommerce' | 'service' | ''>(draft?.industry ?? '');
  const [targetAudience, setTargetAudience] = useState(draft?.targetAudience ?? '');
  const [location, setLocation] = useState(draft?.location ?? '');
  const [language, setLanguage] = useState(draft?.language ?? '');

  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<{
    companyName?: string;
    description?: string;
    industry?: string;
  }>({});

  function validateFields() {
    const nextErrors: {
      companyName?: string;
      description?: string;
      industry?: string;
    } = {};

    if (!companyName.trim()) {
      nextErrors.companyName = 'Emri i kompanisë është i detyrueshëm.';
    } else if (companyName.trim().length < 3) {
      nextErrors.companyName = 'Emri i kompanisë duhet të ketë të paktën 3 karaktere.';
    }

    if (!description.trim()) {
      nextErrors.description = 'Përshkrimi i kompanisë është i detyrueshëm.';
    } else if (description.trim().length < 10) {
      nextErrors.description = 'Shkruani të paktën 10 karaktere për përshkrimin.';
    }

    if (!industry) {
      nextErrors.industry = 'Ju lutem zgjidhni industrinë kryesore.';
    }

    setFieldErrors(nextErrors);

    return Object.keys(nextErrors).length === 0;
  }

  function handleBlur(field: 'companyName' | 'description' | 'industry') {
    // Valido vetëm fushën e dhënë pa prekur të tjerat
    setFieldErrors((prev) => {
      const updated = { ...prev };

      if (field === 'companyName') {
        if (!companyName.trim()) {
          updated.companyName = 'Emri i kompanisë është i detyrueshëm.';
        } else if (companyName.trim().length < 3) {
          updated.companyName = 'Emri i kompanisë duhet të ketë të paktën 3 karaktere.';
        } else {
          delete updated.companyName;
        }
      }

      if (field === 'description') {
        if (!description.trim()) {
          updated.description = 'Përshkrimi i kompanisë është i detyrueshëm.';
        } else if (description.trim().length < 10) {
          updated.description = 'Shkruani të paktën 10 karaktere për përshkrimin.';
        } else {
          delete updated.description;
        }
      }

      if (field === 'industry') {
        if (!industry) {
          updated.industry = 'Ju lutem zgjidhni industrinë kryesore.';
        } else {
          delete updated.industry;
        }
      }

      return updated;
    });
  }

  useEffect(() => {
    const nextDraft: CompanyOnboardingDraft = {
      companyName,
      description,
      industry,
      targetAudience,
      location,
      language,
    };

    try {
      sessionStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(nextDraft));
    } catch {
      // Storage may be unavailable; ignore gracefully.
    }
  }, [companyName, description, industry, targetAudience, location, language]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    const isValid = validateFields();
    if (!isValid) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const payload: Partial<Business> = {
        name: companyName.trim(),
        description: description.trim(),
        industry: industry || null,
        target_audience: targetAudience.trim() || null,
        location: location.trim() || null,
        language: language.trim() || null,
      };

      await apiRequest<Business>('/api/business/me', {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });

      navigate('/app', { replace: true });
    } catch {
      setError('Ndodhi një gabim gjatë ruajtjes. Provo përsëri.');
    } finally {
      setIsLoading(false);
    }
  }

  function handleSkip() {
    navigate('/app', { replace: true });
  }

  return (
    <div className="auth-page">
      <div className="auth-card company-onboarding-card">
        <h1>Na tregoni për biznesin tuaj</h1>
        <p className="auth-hint company-onboarding-hint">
          Këto të dhëna ndihmojnë sistemin dhe inteligjencën artificiale të kuptojnë më mirë biznesin tuaj
          dhe të japin rekomandime më të sakta.
        </p>
        {error && (
          <div className="auth-error" role="alert">
            {error}
          </div>
        )}
        <form className="company-onboarding-form" onSubmit={handleSubmit}>
          <label>
            Emri i kompanisë
            <input
              type="text"
              value={companyName}
              onChange={(e) => setCompanyName(e.target.value)}
              onBlur={() => handleBlur('companyName')}
              disabled={isLoading}
              required
            />
            {fieldErrors.companyName && (
              <span className="field-error" role="status">
                {fieldErrors.companyName}
              </span>
            )}
          </label>

          <label>
            Çfarë bën kompania juaj?{' '}
            <span className="required" aria-hidden="true">
              *
            </span>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              onBlur={() => handleBlur('description')}
              disabled={isLoading}
              required
              rows={3}
              placeholder="P.sh. Shesim produkte kozmetike online në Shqipëri…"
            />
            {fieldErrors.description && (
              <span className="field-error" role="status">
                {fieldErrors.description}
              </span>
            )}
          </label>

          <label>
            Industria kryesore
            <select
              value={industry}
              onChange={(e) => setIndustry(e.target.value as 'ecommerce' | 'service' | '')}
              onBlur={() => handleBlur('industry')}
              disabled={isLoading}
              required
            >
              <option value="">Zgjidhni industrinë</option>
              <option value="ecommerce">Ecommerce</option>
              <option value="service">Shërbime</option>
            </select>
          </label>

          <label>
            Klientët tuaj idealë (target audience)
            <textarea
              value={targetAudience}
              onChange={(e) => setTargetAudience(e.target.value)}
              disabled={isLoading}
              rows={3}
              placeholder="P.sh. gra 25–45 vjeç që kujdesen për lëkurën, biznese të vogla që…"
            />
          </label>

          <label>
            Lokacioni ku operoni
            <input
              type="text"
              value={location}
              onChange={(e) => setLocation(e.target.value)}
              disabled={isLoading}
              placeholder="Shteti / Qyteti (p.sh. Shqipëri, Tiranë)"
            />
          </label>

          <label>
            Gjuha kryesore me klientët
            <input
              type="text"
              value={language}
              onChange={(e) => setLanguage(e.target.value)}
              disabled={isLoading}
              placeholder="P.sh. Shqip, Anglisht…"
            />
          </label>

          <div className="company-onboarding-actions">
            <button type="button" className="btn-secondary" onClick={handleSkip} disabled={isLoading}>
              Më vonë
            </button>
            <button
              type="submit"
              className="btn-primary"
              disabled={
                isLoading ||
                !companyName.trim() ||
                !description.trim() ||
                !industry ||
                Object.keys(fieldErrors).length > 0
              }
            >
              {isLoading ? 'Duke ruajtur…' : 'Vazhdo në panel'}
            </button>
          </div>
        </form>
        <p className="company-onboarding-footnote">
          Ky hap shfaqet menjëherë pas regjistrimit për të personalizuar më mirë eksperiencën tuaj. Ruajtja e
          të dhënave integrohet me backend më vonë.
        </p>
      </div>
    </div>
  );
}

