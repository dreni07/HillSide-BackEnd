import { useCallback, useState } from 'react';
import { useOnboarding } from '../../context/OnboardingContext';
import { useBusinessTypes } from '../../hooks/useBusinessTypes';
import { validateSingleField } from '../../hooks/useOnboardingSubmit';
import { OTHER_VALUE, CATEGORY_LABELS } from '../../constants/onboarding';
import { Modal } from '../Modal';

export function BusinessTypeSelect() {
  const { state, dispatch, isOtherSelected, hasTypeSelection } = useOnboarding();
  const { formData, fieldErrors, isSubmitting } = state;
  const { types, loading, error: fetchError } = useBusinessTypes();

  const [modalOpen, setModalOpen] = useState(false);
  const [modalInput, setModalInput] = useState('');

  const handleBlur = useCallback(() => {
    const error = validateSingleField('businessTypeId', formData, hasTypeSelection);
    dispatch({ type: 'SET_FIELD_ERROR', field: 'businessTypeId', error });
  }, [formData, hasTypeSelection, dispatch]);

  const handleChange = useCallback(
    (val: string) => {
      if (val === OTHER_VALUE) {
        setModalInput(formData.customBusinessType);
        setModalOpen(true);
      } else {
        dispatch({ type: 'SELECT_REAL_TYPE', id: val ? Number(val) : null });
      }
    },
    [formData.customBusinessType, dispatch],
  );

  const handleOtherConfirm = useCallback(() => {
    const trimmed = modalInput.trim();
    if (!trimmed) return;
    dispatch({ type: 'SELECT_OTHER_TYPE', name: trimmed });
    setModalOpen(false);
    setModalInput('');
  }, [modalInput, dispatch]);

  const handleOtherCancel = useCallback(() => {
    setModalOpen(false);
    setModalInput('');
    if (!formData.customBusinessType) {
      dispatch({ type: 'SELECT_REAL_TYPE', id: null });
    }
  }, [formData.customBusinessType, dispatch]);

  const openEditModal = useCallback(() => {
    setModalInput(formData.customBusinessType);
    setModalOpen(true);
  }, [formData.customBusinessType]);

  const selectValue = isOtherSelected ? OTHER_VALUE : (formData.businessTypeId?.toString() ?? '');

  return (
    <>
      <label>
        Lloji i biznesit <span className="required" aria-hidden="true">*</span>

        {loading ? (
          <p style={{ fontSize: '0.875rem', color: '#718096' }}>Duke ngarkuar llojet…</p>
        ) : fetchError ? (
          <p style={{ fontSize: '0.875rem', color: '#e53e3e' }}>{fetchError}</p>
        ) : (
          <>
            <select
              value={selectValue}
              onChange={(e) => handleChange(e.target.value)}
              onBlur={handleBlur}
              disabled={isSubmitting}
              required
            >
              <option value="">Zgjidhni llojin e biznesit</option>
              {Object.entries(types).map(([category, list]) => (
                <optgroup key={category} label={CATEGORY_LABELS[category] ?? category}>
                  {list.map((bt) => (
                    <option key={bt.id} value={bt.id}>{bt.name}</option>
                  ))}
                  <option value={OTHER_VALUE}>Tjetër…</option>
                </optgroup>
              ))}
            </select>

            {isOtherSelected && (
              <span className="custom-type-badge">
                {formData.customBusinessType}
                <button type="button" className="custom-type-edit" onClick={openEditModal}>
                  Ndrysho
                </button>
              </span>
            )}
          </>
        )}

        {fieldErrors.businessTypeId && (
          <span className="field-error" role="status">{fieldErrors.businessTypeId}</span>
        )}
      </label>

      <Modal
        open={modalOpen}
        onClose={handleOtherCancel}
        title="Lloji tjetër i biznesit"
        subtitle="Na tregoni se çfarë lloji biznesi keni. Kjo na ndihmon të personalizojmë eksperiencën për ju."
        footer={
          <>
            <button className="hm-btn hm-btn--ghost" onClick={handleOtherCancel}>Anulo</button>
            <button
              className="hm-btn hm-btn--primary"
              onClick={handleOtherConfirm}
              disabled={!modalInput.trim()}
            >
              Konfirmo
            </button>
          </>
        }
      >
        <label>
          Lloji i biznesit tuaj
          <input
            type="text"
            value={modalInput}
            onChange={(e) => setModalInput(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter') { e.preventDefault(); handleOtherConfirm(); } }}
            placeholder="p.sh. Agjenci Marketingu, Pet Shop…"
            autoFocus
          />
        </label>
      </Modal>
    </>
  );
}
