// Tab Manager - Tab navigation and status management

let currentTab = 'general';

export function switchTab(tabName) {
  // Update tab buttons
  document
    .querySelectorAll('.tab-button')
    .forEach(btn => btn.classList.remove('active'));
  document
    .querySelector(`[onclick="switchTab('${tabName}')"]`)
    .classList.add('active');

  // Update tab panels
  document
    .querySelectorAll('.tab-panel')
    .forEach(panel => (panel.style.display = 'none'));
  document.getElementById(tabName + '-panel').style.display = 'block';

  currentTab = tabName;

  // Update navigation buttons after tab switch
  setTimeout(() => {
    if (window.updateNavigationButtons) {
      window.updateNavigationButtons();
    }
  }, 50);
}

// Get current active tab
export function getCurrentTab() {
  return currentTab;
}

// Check if tab is valid/complete
export function isTabValid(tabName) {
  const panel = document.getElementById(tabName + '-panel');
  if (!panel) {
    return false;
  }

  // Get all input/select/textarea fields in the tab
  const fields = panel.querySelectorAll('input, select, textarea');
  for (const field of fields) {
    if (field.id) {
      // Use validation system to check if field is valid
      if (window.validateField) {
        const isValid = window.validateField(field.id, field.value);
        if (!isValid) {
          return false;
        }
      }
    }
  }
  return true;
}

// Update tab status indicators
export function updateTabStatus(tabName) {
  const tabButton = document.querySelector(
    `[onclick="switchTab('${tabName}')"]`
  );
  if (!tabButton) {
    return;
  }

  let statusIndicator = tabButton.querySelector('.tab-status');
  if (!statusIndicator) {
    // Create status indicator if it doesn't exist
    statusIndicator = document.createElement('span');
    statusIndicator.className = 'tab-status';
    tabButton.appendChild(statusIndicator);
  }

  const isValid = isTabValid(tabName);

  if (isValid) {
    statusIndicator.classList.remove('invalid');
    statusIndicator.classList.add('valid');
    statusIndicator.innerHTML = '●'; // Green dot
  } else {
    statusIndicator.classList.remove('valid');
    statusIndicator.classList.add('invalid');
    statusIndicator.innerHTML = '●'; // Red dot
  }
}

// Update all tab statuses
export function updateAllTabStatuses() {
  const tabs = [
    'general',
    'repository',
    'drupal',
    'services',
    'hosting',
    'workflow',
    'cicd',
    'deployment',
    'dependencies',
    'database',
  ];
  tabs.forEach(tab => {
    if (document.getElementById(tab + '-panel')) {
      updateTabStatus(tab);
    }
  });
}
