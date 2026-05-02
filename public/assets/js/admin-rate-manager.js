(() => {
  const root = document.querySelector('[data-rate-manager]');
  if (!root) {
    return;
  }

  const updateBase = root.getAttribute('data-update-base') || '';
  const modal = root.querySelector('[data-rate-modal]');
  const openButtons = root.querySelectorAll('[data-rate-open]');
  const closeButton = root.querySelector('[data-rate-close]');
  const form = root.querySelector('[data-rate-form]');
  const submitButton = root.querySelector('[data-rate-submit]');
  const errorBox = root.querySelector('[data-rate-error]');
  const pairLabel = root.querySelector('[data-rate-pair]');
  const currentRateLabel = root.querySelector('[data-rate-current]');
  const modeLabel = root.querySelector('[data-rate-mode-label]');
  const rateHint = root.querySelector('[data-rate-hint]');

  if (!modal || !form || !closeButton || !submitButton || !errorBox || !pairLabel || !currentRateLabel || !modeLabel || !rateHint) {
    return;
  }

  const pairIdInput = form.querySelector('input[name="pair_id"]');
  const rateInput = form.querySelector('input[name="new_rate"]');
  const reasonInput = form.querySelector('textarea[name="reason"]');

  if (!pairIdInput || !rateInput || !reasonInput) {
    return;
  }

  const closeModal = () => {
    modal.hidden = true;
    submitButton.disabled = false;
    errorBox.hidden = true;
    errorBox.textContent = '';
  };

  const buildHint = (payload) => {
    if (payload.mode === 'inverse') {
      return `Mode inverse: isi nilai 1 ${payload.to} = x ${payload.from}. Contoh isi 18000 berarti 1 ${payload.to} setara 18000 ${payload.from}.`;
    }
    return `Mode manual: isi nilai 1 ${payload.from} = x ${payload.to}. Contoh isi 17400 berarti 1 ${payload.from} setara 17400 ${payload.to}.`;
  };

  const openModal = (payload) => {
    errorBox.hidden = true;
    errorBox.textContent = '';
    pairIdInput.value = payload.id;
    rateInput.value = payload.rate;
    reasonInput.value = '';
    pairLabel.textContent = payload.pair;
    currentRateLabel.textContent = payload.rate;
    modeLabel.textContent = payload.modeLabel;
    rateHint.textContent = buildHint(payload);
    modal.hidden = false;
    rateInput.focus();
  };

  openButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const id = button.getAttribute('data-id') || '';
      const pair = button.getAttribute('data-pair') || '-';
      const rate = button.getAttribute('data-rate') || '0';
      const mode = button.getAttribute('data-mode') || 'manual';
      const from = button.getAttribute('data-from') || 'FROM';
      const to = button.getAttribute('data-to') || 'TO';
      const modeLabelText = button.getAttribute('data-mode-label') || mode;
      if (id === '') {
        return;
      }
      openModal({ id, pair, rate, mode, from, to, modeLabel: modeLabelText });
    });
  });

  closeButton.addEventListener('click', closeModal);
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const pairId = pairIdInput.value;
    if (pairId === '') {
      return;
    }

    submitButton.disabled = true;
    errorBox.hidden = true;
    errorBox.textContent = '';

    const normalizeRateValue = (raw) => {
      const value = String(raw || '').trim().replace(/\s+/g, '');
      if (value.includes('.') && value.includes(',')) {
        return value.replace(/,/g, '');
      }
      if (!value.includes('.') && value.includes(',')) {
        return value.replace(',', '.');
      }
      return value;
    };

    const normalizedRate = normalizeRateValue(rateInput.value);
    rateInput.value = normalizedRate;

    const formData = new FormData(form);
    try {
      const response = await fetch(`${updateBase}/${pairId}/update`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });

      const json = await response.json();
      const csrfField = form.querySelector('input[type="hidden"][name]:not([name="pair_id"])');
      if (csrfField && json.csrfHash) {
        csrfField.value = json.csrfHash;
      }

      if (!response.ok || json.success !== true) {
        const detailErrors = json.errors && typeof json.errors === 'object'
          ? Object.values(json.errors).filter(Boolean).join(' ')
          : '';
        errorBox.hidden = false;
        errorBox.textContent = detailErrors !== '' ? detailErrors : (json.message || 'Gagal update rate.');
        submitButton.disabled = false;
        return;
      }

      const row = document.getElementById(`pair-row-${pairId}`);
      if (row) {
        const rateNode = row.querySelector('.js-rate');
        const modeNode = row.querySelector('.js-rate-mode');
        const updatedAtNode = row.querySelector('.js-updated-at');
        const updatedByNode = row.querySelector('.js-updated-by');
        if (rateNode) {
          rateNode.textContent = String(json.new_rate_display || json.new_rate || '0');
        }
        if (modeNode && json.rate_mode) {
          modeNode.textContent = String(json.rate_mode);
        }
        if (updatedAtNode) {
          updatedAtNode.textContent = json.updated_at || '-';
        }
        if (updatedByNode) {
          updatedByNode.textContent = json.updated_by || '-';
        }

        const trigger = row.querySelector('[data-rate-open]');
        if (trigger) {
          trigger.setAttribute('data-rate', String(json.new_rate_display || json.new_rate || '0'));
          if (json.rate_mode) {
            trigger.setAttribute('data-mode', String(json.rate_mode));
          }
        }
      }

      closeModal();
    } catch (error) {
      errorBox.hidden = false;
      errorBox.textContent = 'Terjadi kesalahan server.';
      submitButton.disabled = false;
    }
  });
})();
