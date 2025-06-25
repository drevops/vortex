// UI Utilities - Navigation buttons and responsive functionality

// Navigation button control
export function updateNavigationButtons() {
  const nextButton = document.querySelector('.next-button');
  const prevButton = document.querySelector('.prev-button');

  if (nextButton) {
    const isCurrentTabValid = window.validateCurrentTabFields
      ? window.validateCurrentTabFields()
      : true;
    nextButton.disabled = !isCurrentTabValid;

    if (isCurrentTabValid) {
      nextButton.classList.remove('disabled');
    } else {
      nextButton.classList.add('disabled');
    }
  }

  if (prevButton) {
    const currentTab = window.getCurrentTab
      ? window.getCurrentTab()
      : 'general';
    prevButton.disabled = currentTab === 'general';

    if (currentTab === 'general') {
      prevButton.classList.add('disabled');
    } else {
      prevButton.classList.remove('disabled');
    }
  }
}

// Next/Previous navigation
export function goToNextTab() {
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
  const currentTab = window.getCurrentTab ? window.getCurrentTab() : 'general';
  const currentIndex = tabs.indexOf(currentTab);

  if (currentIndex < tabs.length - 1) {
    const nextTab = tabs[currentIndex + 1];
    if (document.getElementById(nextTab + '-panel')) {
      window.switchTab(nextTab);
    }
  }
}

export function goToPreviousTab() {
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
  const currentTab = window.getCurrentTab ? window.getCurrentTab() : 'general';
  const currentIndex = tabs.indexOf(currentTab);

  if (currentIndex > 0) {
    const prevTab = tabs[currentIndex - 1];
    if (document.getElementById(prevTab + '-panel')) {
      window.switchTab(prevTab);
    }
  }
}

// Dynamic scaling based on viewport height
export function updateScaling() {
  const vh = window.innerHeight;
  const minHeight = 600;
  const maxHeight = 1200;
  const minScale = 0.7;
  const maxScale = 1.0;

  // Calculate scale factor based on viewport height
  const normalizedHeight = Math.max(minHeight, Math.min(maxHeight, vh));
  const scaleFactor =
    minScale +
    (maxScale - minScale) *
      ((normalizedHeight - minHeight) / (maxHeight - minHeight));

  // Apply scale factor to CSS custom property
  document.documentElement.style.setProperty('--scale-factor', scaleFactor);
}
