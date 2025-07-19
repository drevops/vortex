Vortex Web Installer — Implementation Plan

1. Script Architecture
   •	Modularise the codebase by splitting installer scripts into logically separated files, grouping related concerns (e.g. UI rendering, validation logic, navigation).

2. Form Layout and Validation
   •	Field validation messages must appear directly below each form field, not underneath the field’s description text, to ensure immediate contextual feedback for users.
   •	Field validation rules should be defined within the HTML, leveraging native HTML5 attributes (required, pattern, minlength, etc.) wherever possible. Custom JavaScript validation should be minimised and only used for non-standard logic.
   •	Next button state should be dynamically controlled:
   •	Remains disabled until all required fields on the current tab are valid.
   •	Enabled once validation passes.

3. Tab Navigation & Status Indicators
   •	Restore visual feedback to vertical tab navigation:
   •	Each tab should display a green dot if the corresponding form is complete and valid.
   •	Display a red dot if required fields are incomplete or invalid.
   •	Sync vertical tabs with the Prompt Manager configuration:
   •	Reassess the structure defined in the Prompt Manager.
   •	Ensure that all tabs in the UI correspond to actual groupings defined in the Prompt Manager.
   •	Remove or rename the Advanced tab, as it does not currently exist in Prompt Manager.

4. Field Styling Improvements
   •	Radios and checkboxes:
   •	Remove question mark icons (or tooltips) currently displayed alongside these elements.
   •	Eliminate separation <div> elements between individual options to ensure a more compact and coherent layout.
   •	Align radio buttons horizontally to improve visual grouping.
   •	Previous and Next buttons:
   •	Add “Previous” and “Next” buttons at the bottom of each step.
   •	The “Next” button must follow the validation gating described above.

5. Micro Status Panel
   •	Add a micro status panel embedded within the installer UI.
   •	This panel should offer quick-access links, such as:
   •	The project’s GitHub repository
   •	(Optionally) a link to the documentation or support portal
   •	Keep this panel visually minimal but accessible throughout the installer flow.

6. Icon Consistency
   •	Audit and update icon usage across the installer.
   •	Ensure all icons are correct, consistent with the design language, and accessible (e.g. use aria-label where appropriate).
