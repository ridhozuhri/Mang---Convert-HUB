(() => {
  const body = document.body;
  const toggle = document.getElementById('admin-menu-toggle');
  const overlay = document.getElementById('admin-overlay');

  if (toggle && overlay) {
    toggle.addEventListener('click', () => {
      body.classList.toggle('admin-menu-open');
    });

    overlay.addEventListener('click', () => {
      body.classList.remove('admin-menu-open');
    });
  }

  const tabButtons = Array.from(document.querySelectorAll('[data-tab]'));
  const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));

  if (tabButtons.length > 0 && tabPanels.length > 0) {
    const setActiveTab = (tab) => {
      tabButtons.forEach((button) => {
        button.classList.toggle('active', button.getAttribute('data-tab') === tab);
      });

      tabPanels.forEach((panel) => {
        panel.hidden = panel.getAttribute('data-tab-panel') !== tab;
      });
    };

    tabButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const tab = button.getAttribute('data-tab');
        if (tab) {
          setActiveTab(tab);
        }
      });
    });

    const firstActive = tabButtons.find((button) => button.classList.contains('active'));
    setActiveTab((firstActive || tabButtons[0]).getAttribute('data-tab'));
  }

  const onceForms = Array.from(document.querySelectorAll('form[data-once-submit]'));
  onceForms.forEach((form) => {
    form.addEventListener('submit', () => {
      const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
      submitButtons.forEach((button) => {
        button.disabled = true;
      });
      setTimeout(() => {
        submitButtons.forEach((button) => {
          button.disabled = false;
        });
      }, 5000);
    });
  });

  const selectAllOrders = document.querySelector('[data-select-all-orders]');
  if (selectAllOrders) {
    selectAllOrders.addEventListener('change', () => {
      document.querySelectorAll('.order-checkbox').forEach((checkbox) => {
        checkbox.checked = selectAllOrders.checked;
      });
    });
  }
})();
