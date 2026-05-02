(() => {
  const form = document.querySelector('[data-pair-form]');
  if (!form) {
    return;
  }

  const fromSelect = form.querySelector('[data-asset-from]');
  const toSelect = form.querySelector('[data-asset-to]');
  const rateModeSelect = form.querySelector('[data-rate-mode]');
  const hintRate = form.querySelector('[data-hint-rate]');
  const hintMin = form.querySelector('[data-hint-min]');
  const hintMax = form.querySelector('[data-hint-max]');

  if (!fromSelect || !toSelect || !rateModeSelect || !hintRate || !hintMin || !hintMax) {
    return;
  }

  const getCode = (select) => {
    const option = select.options[select.selectedIndex];
    if (!option) {
      return '';
    }
    return String(option.getAttribute('data-code') || '').toUpperCase();
  };

  const updateHints = () => {
    const fromCode = getCode(fromSelect);
    const toCode = getCode(toSelect);
    const rateMode = String(rateModeSelect.value || 'manual');

    const minExample = fromCode === 'IDR' ? '150000' : '10.50';
    const maxExample = fromCode === 'IDR' ? '50000000' : '5000.75';
    hintMin.textContent = `Contoh ${fromCode || 'FROM'}: ${minExample}`;
    hintMax.textContent = `Contoh ${fromCode || 'FROM'}: ${maxExample}`;

    if (fromCode !== '' && toCode !== '') {
      if (rateMode === 'inverse') {
        hintRate.textContent = `Mode inverse: isi nilai 1 ${toCode} = x ${fromCode}. Contoh: ${fromCode} -> ${toCode}, isi 18000 berarti 1 ${toCode} = 18000 ${fromCode}.`;
      } else {
        hintRate.textContent = `Mode manual: isi nilai langsung 1 ${fromCode} = x ${toCode}. Contoh: ${fromCode} -> ${toCode}, isi 17400 berarti 1 ${fromCode} = 17400 ${toCode}.`;
      }
      return;
    }

    hintRate.textContent = 'Pilih From/To asset lalu pilih Rate Mode untuk melihat contoh format rate.';
  };

  fromSelect.addEventListener('change', updateHints);
  toSelect.addEventListener('change', updateHints);
  rateModeSelect.addEventListener('change', updateHints);
  updateHints();
})();
