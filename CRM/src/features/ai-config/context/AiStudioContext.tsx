import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { apiRequest, type Business } from '../../../services/api';
import { newClientId } from '../data/aiFormUtils';
import { flowNodeToDomainId } from '../data/flowNodeMap';
import { buildSaveAiConfigPayload } from '../requests/buildAiConfigPayload';
import { getDomainItem } from '../data/studioPalette';
import type { ApiAiConfigSaveData, ApiAiConfigShowData, SaveToServerResult } from '../types/apiAiConfig';
import { mapShowDataToDrafts } from '../utils/mapApiToDrafts';
import type {
  AiPersonalityDraft,
  AiRestrictionsDraft,
  AiSalesmanDraft,
  ConfigDomainId,
  ExpectedQuestionDraft,
  StudioSelection,
} from '../types/studio';
import type { AiStudioContextValue } from './aiStudioContextTypes';

const AiStudioContext = createContext<AiStudioContextValue | null>(null);

const defaultPersonality: AiPersonalityDraft = {
  tone: 'professional',
  response_style: 'balanced',
  language: 'en',
  greeting_message: '',
  farewell_message: '',
  custom_instructions: '',
};

const defaultRestrictions: AiRestrictionsDraft = {
  allowed_topics: [],
  restricted_topics: [],
  blocked_words: [],
  max_response_length: null,
  content_guidelines: '',
};

const defaultSalesman: AiSalesmanDraft = {
  sales_approach: 'consultative',
  upsell_enabled: false,
  product_knowledge: '',
  pricing_info: '',
  call_to_action: '',
  objection_handling: '',
};

function resolveDomainFromSelection(selection: StudioSelection): ConfigDomainId | null {
  if (!selection) return null;
  if (selection.source === 'palette') return selection.itemId;
  return flowNodeToDomainId(selection.nodeId);
}

export function AiStudioProvider({ children }: { children: ReactNode }) {
  const [orchestrationTitle] = useState('Agent Orchestration Studio');
  const [orchestrationSubtitle] = useState('Configure AI for your business');
  const [librarySearch, setLibrarySearch] = useState('');
  const [selection, setSelection] = useState<StudioSelection>({
    source: 'palette',
    itemId: 'ai-personality',
  });

  const [personality, setPersonality] = useState<AiPersonalityDraft>(defaultPersonality);
  const [restrictions, setRestrictions] = useState<AiRestrictionsDraft>(defaultRestrictions);
  const [salesman, setSalesman] = useState<AiSalesmanDraft>(defaultSalesman);
  const [expectedQuestions, setExpectedQuestions] = useState<ExpectedQuestionDraft[]>([]);

  const [isBootstrapping, setIsBootstrapping] = useState(true);
  const [bootstrapError, setBootstrapError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const hydrateDraft = useCallback((payload: ApiAiConfigShowData) => {
    const mapped = mapShowDataToDrafts(payload);
    setPersonality(mapped.personality);
    setRestrictions(mapped.restrictions);
    setSalesman(mapped.salesman);
    setExpectedQuestions(mapped.expectedQuestions);
  }, []);

  const reloadFromServer = useCallback(async () => {
    setBootstrapError(null);
    setIsBootstrapping(true);
    try {
      const business = await apiRequest<Business>('/api/business/me');
      const data = await apiRequest<ApiAiConfigShowData>(`/api/businesses/${business.id}/ai-config`);
      hydrateDraft(data);
    } catch (e) {
      setPersonality(defaultPersonality);
      setRestrictions(defaultRestrictions);
      setSalesman(defaultSalesman);
      setExpectedQuestions([]);
      const msg =
        e instanceof Error
          ? e.message
          : 'Nuk u ngarkua konfigurimi. Kontrolloni lidhjen ose identifikohuni përsëri.';
      setBootstrapError(msg);
    } finally {
      setIsBootstrapping(false);
    }
  }, [hydrateDraft]);

  useEffect(() => {
    void reloadFromServer();
  }, [reloadFromServer]);

  const selectFromPalette = useCallback((itemId: ConfigDomainId) => {
    setSelection({ source: 'palette', itemId });
  }, []);

  const selectFromCanvas = useCallback((nodeId: string) => {
    setSelection({ source: 'canvas', nodeId });
  }, []);

  const clearInspector = useCallback(() => {
    setSelection(null);
  }, []);

  const updatePersonality = useCallback((patch: Partial<AiPersonalityDraft>) => {
    setPersonality((p) => ({ ...p, ...patch }));
  }, []);

  const updateRestrictions = useCallback((patch: Partial<AiRestrictionsDraft>) => {
    setRestrictions((r) => ({ ...r, ...patch }));
  }, []);

  const updateSalesman = useCallback((patch: Partial<AiSalesmanDraft>) => {
    setSalesman((s) => ({ ...s, ...patch }));
  }, []);

  const addExpectedQuestion = useCallback(() => {
    setExpectedQuestions((list) => [
      ...list,
      { clientId: newClientId('eq'), question: '', answer: '' },
    ]);
  }, []);

  const updateExpectedQuestion = useCallback(
    (clientId: string, patch: Partial<Pick<ExpectedQuestionDraft, 'question' | 'answer'>>) => {
      setExpectedQuestions((list) =>
        list.map((row) => (row.clientId === clientId ? { ...row, ...patch } : row)),
      );
    },
    [],
  );

  const removeExpectedQuestion = useCallback((clientId: string) => {
    setExpectedQuestions((list) => list.filter((row) => row.clientId !== clientId));
  }, []);

  const saveToServer = useCallback(async (): Promise<SaveToServerResult> => {
    setIsSaving(true);
    try {
      const business = await apiRequest<Business>('/api/business/me');
      const payload = buildSaveAiConfigPayload(personality, restrictions, salesman, expectedQuestions);
      const saved = await apiRequest<ApiAiConfigSaveData>(
        `/api/businesses/${business.id}/ai-config/save`,
        {
          method: 'POST',
          body: JSON.stringify(payload),
        },
      );
      hydrateDraft({
        config: saved.config,
        expected_questions: saved.expected_questions,
      });
      return { ok: true, message: 'Konfigurimi i AI u ruajt me sukses.' };
    } catch (e) {
      return {
        ok: false,
        message:
          e instanceof Error
            ? e.message
            : 'Ruajtja dështoi. Ju lutemi provoni përsëri ose kontrolloni lidhjen.',
      };
    } finally {
      setIsSaving(false);
    }
  }, [personality, restrictions, salesman, expectedQuestions, hydrateDraft]);

  const selectedDomainId = useMemo(() => resolveDomainFromSelection(selection), [selection]);

  const inspectorHeader = useMemo(() => {
    if (!selectedDomainId) return null;
    const item = getDomainItem(selectedDomainId);
    if (!item) return null;
    return {
      title: item.label,
      subtitle: item.hint ?? '',
      category: item.category,
    };
  }, [selectedDomainId]);

  const value = useMemo<AiStudioContextValue>(
    () => ({
      orchestrationTitle,
      orchestrationSubtitle,
      librarySearch,
      setLibrarySearch,
      selection,
      selectFromPalette,
      selectFromCanvas,
      clearInspector,
      selectedDomainId,
      inspectorHeader,
      personality,
      updatePersonality,
      restrictions,
      updateRestrictions,
      salesman,
      updateSalesman,
      expectedQuestions,
      addExpectedQuestion,
      updateExpectedQuestion,
      removeExpectedQuestion,
      isBootstrapping,
      bootstrapError,
      reloadFromServer,
      isSaving,
      saveToServer,
    }),
    [
      orchestrationTitle,
      orchestrationSubtitle,
      librarySearch,
      selection,
      selectFromPalette,
      selectFromCanvas,
      clearInspector,
      selectedDomainId,
      inspectorHeader,
      personality,
      updatePersonality,
      restrictions,
      updateRestrictions,
      salesman,
      updateSalesman,
      expectedQuestions,
      addExpectedQuestion,
      updateExpectedQuestion,
      removeExpectedQuestion,
      isBootstrapping,
      bootstrapError,
      reloadFromServer,
      isSaving,
      saveToServer,
    ],
  );

  return <AiStudioContext.Provider value={value}>{children}</AiStudioContext.Provider>;
}

export function useAiStudioContext(): AiStudioContextValue {
  const ctx = useContext(AiStudioContext);
  if (!ctx) {
    throw new Error('useAiStudioContext must be used within AiStudioProvider');
  }
  return ctx;
}
