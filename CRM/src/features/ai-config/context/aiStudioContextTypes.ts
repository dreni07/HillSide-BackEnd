import type {
  AiBehaviourDraft,
  AiPersonalityDraft,
  AiRestrictionsDraft,
  AiSalesmanDraft,
  ConfigDomainId,
  ExpectedQuestionDraft,
  StudioInspectorHeaderModel,
  StudioSelection,
} from '../types/studio';
import type { SaveToServerResult } from '../types/apiAiConfig';

export interface AiStudioContextValue {
  orchestrationTitle: string;
  orchestrationSubtitle: string;
  librarySearch: string;
  setLibrarySearch: (q: string) => void;
  selection: StudioSelection;
  selectFromPalette: (itemId: ConfigDomainId) => void;
  selectFromCanvas: (nodeId: string) => void;
  clearInspector: () => void;
  selectedDomainId: ConfigDomainId | null;
  inspectorHeader: StudioInspectorHeaderModel | null;

  personality: AiPersonalityDraft;
  updatePersonality: (patch: Partial<AiPersonalityDraft>) => void;

  restrictions: AiRestrictionsDraft;
  updateRestrictions: (patch: Partial<AiRestrictionsDraft>) => void;

  salesman: AiSalesmanDraft;
  updateSalesman: (patch: Partial<AiSalesmanDraft>) => void;

  expectedQuestions: ExpectedQuestionDraft[];
  addExpectedQuestion: () => void;
  updateExpectedQuestion: (clientId: string, patch: Partial<Pick<ExpectedQuestionDraft, 'question' | 'answer'>>) => void;
  removeExpectedQuestion: (clientId: string) => void;

  /** Initial load of business AI config + expected questions */
  isBootstrapping: boolean;
  bootstrapError: string | null;
  reloadFromServer: () => Promise<void>;

  /** Persist full draft to API */
  isSaving: boolean;
  saveToServer: () => Promise<SaveToServerResult>;

  behaviourDraft: AiBehaviourDraft;
  /** Incremented when server data is applied so the flow canvas re-mounts with saved nodes/edges. */
  flowCanvasKey: number;
  registerFlowSnapshotGetter: (getter: (() => string | null) | null) => void;
}
