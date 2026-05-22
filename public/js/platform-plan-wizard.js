(function () {
  const root = document.getElementById('plan-wizard');
  if (!root) return;

  const schemas = JSON.parse(root.dataset.moduleSchemas || '{}');
  const panels = root.querySelectorAll('.wizard-panel');
  const stepButtons = root.querySelectorAll('.wizard-steps .nav-link');
  const prevBtn = document.getElementById('wizard-prev');
  const nextBtn = document.getElementById('wizard-next');
  const submitBtn = document.getElementById('wizard-submit');
  const form = document.getElementById('plan-form');
  let currentStep = 1;
  let activeModuleKey = null;

  function showStep(step) {
    currentStep = step;
    panels.forEach((p) => {
      p.classList.toggle('d-none', parseInt(p.dataset.panel, 10) !== step);
    });
    stepButtons.forEach((b) => {
      b.classList.toggle('active', parseInt(b.dataset.step, 10) === step);
    });
    prevBtn.disabled = step === 1;
    nextBtn.classList.toggle('d-none', step === 3);
    submitBtn.classList.toggle('d-none', step !== 3);
    if (step === 3) buildReview();
  }

  stepButtons.forEach((btn) => {
    btn.addEventListener('click', () => showStep(parseInt(btn.dataset.step, 10)));
  });

  prevBtn.addEventListener('click', () => showStep(currentStep - 1));
  nextBtn.addEventListener('click', () => showStep(currentStep + 1));

  root.querySelectorAll('.module-toggle').forEach((toggle) => {
    toggle.addEventListener('change', (e) => {
      const row = e.target.closest('tr');
      const editBtn = row.querySelector('.edit-limits-btn');
      if (editBtn) editBtn.disabled = !e.target.checked;
    });
  });

  const modalEl = document.getElementById('moduleLimitsModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  const fieldsEl = document.getElementById('moduleLimitsFields');
  const titleEl = document.getElementById('moduleLimitsModalTitle');

  root.querySelectorAll('.edit-limits-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      activeModuleKey = btn.dataset.module;
      const schema = schemas[activeModuleKey]?.limits || {};
      const row = btn.closest('tr');
      const hidden = row.querySelector('.limits-json');
      const current = JSON.parse(hidden.value || '{}');

      titleEl.textContent =
        (document.documentElement.lang === 'ar' ? 'تعديل حدود الاستخدام - ' : 'Edit limits - ') +
        btn.dataset.moduleName;

      fieldsEl.innerHTML = '';
      Object.entries(schema).forEach(([key, def]) => {
        const wrap = document.createElement('div');
        wrap.className = 'mb-3';
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = key.replace(/_/g, ' ');
        wrap.appendChild(label);

        if (def.type === 'boolean') {
          const check = document.createElement('input');
          check.type = 'checkbox';
          check.className = 'form-check-input';
          check.dataset.limitKey = key;
          check.checked = !!current[key];
          wrap.appendChild(check);
        } else {
          const input = document.createElement('input');
          input.type = 'number';
          input.className = 'form-control';
          input.dataset.limitKey = key;
          input.value = current[key] ?? def.default ?? '';
          wrap.appendChild(input);
        }
        fieldsEl.appendChild(wrap);
      });

      modal.show();
    });
  });

  document.getElementById('saveModuleLimits')?.addEventListener('click', () => {
    if (!activeModuleKey) return;
    const limits = {};
    fieldsEl.querySelectorAll('[data-limit-key]').forEach((el) => {
      const key = el.dataset.limitKey;
      limits[key] = el.type === 'checkbox' ? el.checked : parseInt(el.value, 10) || 0;
    });
    const row = root.querySelector(`tr[data-module-key="${activeModuleKey}"]`);
    if (row) {
      row.querySelector('.limits-json').value = JSON.stringify(limits);
      row.querySelector('.limits-summary').textContent = Object.entries(limits)
        .filter(([, v]) => v)
        .map(([k, v]) => `${k}: ${v}`)
        .slice(0, 3)
        .join(', ') || '—';
    }
    modal.hide();
  });

  function buildReview() {
    const summary = document.getElementById('review-summary');
    if (!summary || !form) return;
    const fd = new FormData(form);
    summary.innerHTML = `
      <p><strong>${fd.get('name')}</strong> — ${fd.get('price')} ${fd.get('currency')} / ${fd.get('billing_cycle')}</p>
      <p class="text-muted">${fd.get('description') || ''}</p>
      <p>${fd.get('is_active') ? '✓ Active' : 'Inactive'}</p>
    `;
  }

  showStep(1);
})();
