import { Plus, Trash2 } from 'lucide-react';
import {
  AI_RESPONSE_STYLE_LABELS,
  AI_RESPONSE_STYLE_VALUES,
  AI_TONE_LABELS,
  AI_TONE_VALUES,
  SALES_APPROACH_LABELS,
  SALES_APPROACH_VALUES,
} from '../../../data/aiSchemaConstants';
import { arrayToLines, linesToArray } from '../../../data/aiFormUtils';
import { useAiStudio } from '../../../hooks/useAiStudio';
import type { ConfigDomainId } from '../../../types/studio';

export function InspectorDomainForms({ domainId }: { domainId: ConfigDomainId }) {
  const {
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
  } = useAiStudio();

  if (domainId === 'ai-personality') {
    return (
      <div className="studio-inspector-body">
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Tone (ai_personalities.tone)</span>
          <select
            className="studio-inspector-select"
            value={personality.tone}
            onChange={(e) => updatePersonality({ tone: e.target.value as typeof personality.tone })}
          >
            {AI_TONE_VALUES.map((v) => (
              <option key={v} value={v}>
                {AI_TONE_LABELS[v]}
              </option>
            ))}
          </select>
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Response style (response_style)</span>
          <select
            className="studio-inspector-select"
            value={personality.response_style}
            onChange={(e) =>
              updatePersonality({ response_style: e.target.value as typeof personality.response_style })
            }
          >
            {AI_RESPONSE_STYLE_VALUES.map((v) => (
              <option key={v} value={v}>
                {AI_RESPONSE_STYLE_LABELS[v]}
              </option>
            ))}
          </select>
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Language (language)</span>
          <input
            type="text"
            className="studio-inspector-input"
            value={personality.language}
            onChange={(e) => updatePersonality({ language: e.target.value })}
            placeholder="en"
            maxLength={16}
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Greeting message (greeting_message)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={3}
            value={personality.greeting_message}
            onChange={(e) => updatePersonality({ greeting_message: e.target.value })}
            placeholder="Optional opening line when a conversation starts."
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Farewell message (farewell_message)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={3}
            value={personality.farewell_message}
            onChange={(e) => updatePersonality({ farewell_message: e.target.value })}
            placeholder="Optional closing line when a chat ends."
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Custom instructions (custom_instructions)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={5}
            value={personality.custom_instructions}
            onChange={(e) => updatePersonality({ custom_instructions: e.target.value })}
            placeholder="Extra rules for how the AI should sound or behave."
          />
        </div>
      </div>
    );
  }

  if (domainId === 'ai-restrictions') {
    return (
      <div className="studio-inspector-body">
        <p className="studio-inspector-hint">
          List values one per line. Stored as JSON arrays on <code>allowed_topics</code>,{' '}
          <code>restricted_topics</code>, and <code>blocked_words</code>.
        </p>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Allowed topics (allowed_topics)</span>
          <textarea
            className="studio-inspector-textarea studio-inspector-textarea--mono"
            rows={4}
            value={arrayToLines(restrictions.allowed_topics)}
            onChange={(e) => updateRestrictions({ allowed_topics: linesToArray(e.target.value) })}
            placeholder="Shipping&#10;Returns&#10;…"
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Restricted topics (restricted_topics)</span>
          <textarea
            className="studio-inspector-textarea studio-inspector-textarea--mono"
            rows={4}
            value={arrayToLines(restrictions.restricted_topics)}
            onChange={(e) => updateRestrictions({ restricted_topics: linesToArray(e.target.value) })}
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Blocked words (blocked_words)</span>
          <textarea
            className="studio-inspector-textarea studio-inspector-textarea--mono"
            rows={3}
            value={arrayToLines(restrictions.blocked_words)}
            onChange={(e) => updateRestrictions({ blocked_words: linesToArray(e.target.value) })}
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Max response length (max_response_length)</span>
          <input
            type="number"
            className="studio-inspector-input studio-inspector-input--narrow"
            min={0}
            value={restrictions.max_response_length ?? ''}
            onChange={(e) => {
              const v = e.target.value;
              updateRestrictions({
                max_response_length: v === '' ? null : Math.max(0, Number.parseInt(v, 10) || 0),
              });
            }}
            placeholder="Characters (optional)"
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Content guidelines (content_guidelines)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={5}
            value={restrictions.content_guidelines}
            onChange={(e) => updateRestrictions({ content_guidelines: e.target.value })}
            placeholder="What the AI must avoid or always respect when talking to customers."
          />
        </div>
      </div>
    );
  }

  if (domainId === 'ai-salesman') {
    return (
      <div className="studio-inspector-body">
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Sales approach (sales_approach)</span>
          <select
            className="studio-inspector-select"
            value={salesman.sales_approach}
            onChange={(e) =>
              updateSalesman({ sales_approach: e.target.value as typeof salesman.sales_approach })
            }
          >
            {SALES_APPROACH_VALUES.map((v) => (
              <option key={v} value={v}>
                {SALES_APPROACH_LABELS[v]}
              </option>
            ))}
          </select>
        </div>
        <div className="studio-inspector-field studio-inspector-field--row">
          <label className="studio-inspector-check-label">
            <input
              type="checkbox"
              checked={salesman.upsell_enabled}
              onChange={(e) => updateSalesman({ upsell_enabled: e.target.checked })}
            />
            <span>Upsell enabled (upsell_enabled)</span>
          </label>
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Product knowledge (product_knowledge)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={4}
            value={salesman.product_knowledge}
            onChange={(e) => updateSalesman({ product_knowledge: e.target.value })}
            placeholder="Facts the AI can use about your products or services."
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Pricing info (pricing_info)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={3}
            value={salesman.pricing_info}
            onChange={(e) => updateSalesman({ pricing_info: e.target.value })}
            placeholder="How to talk about prices, plans, or quotes."
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Call to action (call_to_action)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={3}
            value={salesman.call_to_action}
            onChange={(e) => updateSalesman({ call_to_action: e.target.value })}
            placeholder="What you want the AI to suggest next (book, buy, reply, etc.)."
          />
        </div>
        <div className="studio-inspector-field">
          <span className="studio-inspector-label">Objection handling (objection_handling)</span>
          <textarea
            className="studio-inspector-textarea"
            rows={4}
            value={salesman.objection_handling}
            onChange={(e) => updateSalesman({ objection_handling: e.target.value })}
            placeholder="How to respond when the shopper hesitates or pushes back."
          />
        </div>
      </div>
    );
  }

  return (
    <div className="studio-inspector-body">
      <p className="studio-inspector-hint">
        Each pair is stored as one row in <code>ai_expected_questions</code> (question + answer). Use the{' '}
        <strong>exact</strong> wording you expect from customers so the AI can match it reliably.
      </p>
      <div className="studio-eq-toolbar">
        <button type="button" className="studio-btn studio-btn--primary studio-btn--sm" onClick={addExpectedQuestion}>
          <Plus size={16} aria-hidden />
          Add question
        </button>
      </div>
      {expectedQuestions.length === 0 ? (
        <p className="studio-inspector-placeholder">No expected questions yet. Add one to define a fixed Q → A.</p>
      ) : (
        <ul className="studio-eq-list">
          {expectedQuestions.map((row, index) => (
            <li key={row.clientId} className="studio-eq-card">
              <div className="studio-eq-card__head">
                <span className="studio-eq-card__index">#{index + 1}</span>
                <button
                  type="button"
                  className="studio-icon-btn studio-icon-btn--danger-ghost"
                  aria-label="Remove question"
                  onClick={() => removeExpectedQuestion(row.clientId)}
                >
                  <Trash2 size={16} />
                </button>
              </div>
              <div className="studio-inspector-field">
                <span className="studio-inspector-label">Question</span>
                <textarea
                  className="studio-inspector-textarea"
                  rows={2}
                  value={row.question}
                  onChange={(e) => updateExpectedQuestion(row.clientId, { question: e.target.value })}
                  placeholder="Exact text the customer might send"
                />
              </div>
              <div className="studio-inspector-field">
                <span className="studio-inspector-label">Answer</span>
                <textarea
                  className="studio-inspector-textarea"
                  rows={3}
                  value={row.answer}
                  onChange={(e) => updateExpectedQuestion(row.clientId, { answer: e.target.value })}
                  placeholder="The reply your AI should use for that exact question"
                />
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
