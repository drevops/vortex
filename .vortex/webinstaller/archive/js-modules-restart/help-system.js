// Help System - Help sidebar and content display

export function showHelp(buttonElement) {
  // Find the help content in the same form group
  const formGroup = buttonElement.closest('.form-group');
  const helpContent = formGroup.querySelector('.field-help-extended');

  if (helpContent) {
    updateHelpContentFromElement(helpContent);
  } else {
    updateHelpContentDefault();
  }

  // Show the help sidebar and form overlay
  const sidebar = document.getElementById('helpSidebar');
  const overlay = document.getElementById('formOverlay');

  sidebar.classList.add('active');
  overlay.classList.add('active');
}

export function closeHelpSidebar() {
  const sidebar = document.getElementById('helpSidebar');
  const overlay = document.getElementById('formOverlay');

  sidebar.classList.remove('active');
  overlay.classList.remove('active');
}

function updateHelpContentFromElement(helpElement) {
  const helpContentEl = document.getElementById('helpContent');
  const helpTitleEl = document.querySelector('.help-title');

  // Extract the title from the h4 element
  const titleElement = helpElement.querySelector('h4');
  const title = titleElement ? titleElement.textContent : 'Field Help';

  // Get all content except the h4 title
  const contentElements = Array.from(helpElement.children).filter(
    el => el.tagName !== 'H4'
  );
  const contentHtml = contentElements.map(el => el.outerHTML).join('');

  helpTitleEl.textContent = title;
  helpContentEl.innerHTML = `
        <div class="help-section">
            ${contentHtml}
        </div>
    `;
}

function updateHelpContentDefault() {
  const helpContentEl = document.getElementById('helpContent');
  const helpTitleEl = document.querySelector('.help-title');

  helpTitleEl.textContent = 'Field Help';
  helpContentEl.innerHTML = `
        <div class="help-placeholder">
            No help content available for this field.
        </div>
    `;
}

// Setup help system event listeners
export function setupHelpSystemListeners() {
  // Close sidebar on Escape key
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeHelpSidebar();
    }
  });

  // Close sidebar when clicking on the form overlay
  const overlay = document.getElementById('formOverlay');
  if (overlay) {
    overlay.addEventListener('click', function () {
      closeHelpSidebar();
    });
  }
}
