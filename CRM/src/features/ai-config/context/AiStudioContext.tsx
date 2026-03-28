import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';
import { newClientId } from '../data/aiFormUtils';
import { flowNodeToDomainId } from '../data/flowNodeMap';
import { buildSaveAiConfigPayload } from '../requests/buildAiConfigPayload';
import { fetchAiConfigShow, fetchAuthenticatedBusiness, saveAiConfig } from '../requests/aiConfigRequests';
import { getDomainItem } from '../data/studioPalette';
import type { ApiAiConfigShowData, SaveToServerResult } from '../types/apiAiConfig';
import { mapShowDataToDrafts } from '../utils/mapApiToDrafts';
import type {
  AiBehaviourDraft,
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

const defaultBehaviour: AiBehaviourDraft = {
  orchestration_title: 'Agent Orchestration Studio',
  orchestration_subtitle: 'Configure AI for your business',
  flow_graph_json: null,
};

function resolveDomainFromSelection(selection: StudioSelection): ConfigDomainId | null {
  if (!selection) return null;
  if (selection.source === 'palette') return selection.itemId;
  return flowNodeToDomainId(selection.nodeId);
}

export function AiStudioProvider({ children }: { children: ReactNode }) {
  const [librarySearch, setLibrarySearch] = useState('');
  const [selection, setSelection] = useState<StudioSelection>({
    source: 'palette',
    itemId: 'ai-personality',
  });

  const [personality, setPersonality] = useState<AiPersonalityDraft>(defaultPersonality);
  const [restrictions, setRestrictions] = useState<AiRestrictionsDraft>(defaultRestrictions);
  const [salesman, setSalesman] = useState<AiSalesmanDraft>(defaultSalesman);
  const [expectedQuestions, setExpectedQuestions] = useState<ExpectedQuestionDraft[]>([]);
  const [behaviourDraft, setBehaviourDraft] = useState<AiBehaviourDraft>(defaultBehaviour);
  const [flowCanvasKey, setFlowCanvasKey] = useState(0);

  const [isBootstrapping, setIsBootstrapping] = useState(true);
  const [bootstrapError, setBootstrapError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  const flowSnapshotGetterRef = useRef<(() => string | null) | null>(null);
  const registerFlowSnapshotGetter = useCallback((getter: (() => string | null) | null) => {
    flowSnapshotGetterRef.current = getter;
  }, []);

  const hydrateDraft = useCallback((payload: ApiAiConfigShowData) => {
    const mapped = mapShowDataToDrafts(payload);
    setPersonality(mapped.personality);
    setRestrictions(mapped.restrictions);
    setSalesman(mapped.salesman);
    setExpectedQuestions(mapped.expectedQuestions);
    setBehaviourDraft(mapped.behaviour);
    setFlowCanvasKey((k) => k + 1);
  }, []);

  const reloadFromServer = useCallback(async () => {
    setBootstrapError(null);
    setIsBootstrapping(true);
    try {
      const business = await fetchAuthenticatedBusiness();
      const data = await fetchAiConfigShow(business.id);
      hydrateDraft(data);
    } catch (e) {
      setPersonality(defaultPersonality);
      setRestrictions(defaultRestrictions);
      setSalesman(defaultSalesman);
      setExpectedQuestions([]);
      setBehaviourDraft(defaultBehaviour);
      setFlowCanvasKey((k) => k + 1);
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
      const business = await fetchAuthenticatedBusiness();
      const flowSnap = flowSnapshotGetterRef.current?.();
      const payload = buildSaveAiConfigPayload(
        personality,
        restrictions,
        salesman,
        expectedQuestions,
        behaviourDraft,
        flowSnap,
      );
      const saved = await saveAiConfig(business.id, payload);
      hydrateDraft({
        config: saved.config,
        expected_questions: saved.expected_questions,
        behaviour: saved.behaviour ?? null,
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
  }, [personality, restrictions, salesman, expectedQuestions, behaviourDraft, hydrateDraft]);

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
      orchestrationTitle: behaviourDraft.orchestration_title,
      orchestrationSubtitle: behaviourDraft.orchestration_subtitle ?? '',
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
      behaviourDraft,
      flowCanvasKey,
      registerFlowSnapshotGetter,
    }),
    [
      behaviourDraft,
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
      flowCanvasKey,
      registerFlowSnapshotGetter,
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
