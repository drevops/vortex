/**
 * @file
 * YS Base module JavaScript behaviors.
 */

((Drupal) => {
  Drupal.behaviors.ysBase = {
    attach(context) {
      this.initCounterBlock(context);
    },

    /**
     * Counter block functionality.
     *
     * @param {HTMLElement} context
     *   Context element to search for counter blocks.
     */
    initCounterBlock(context) {
      const counterBlocks = context.querySelectorAll('[data-ys-base-counter]');

      counterBlocks.forEach(function processBlock(block) {
        // Skip if already processed.
        if (block.classList.contains('ys-base-counter-processed')) {
          return;
        }

        block.classList.add('ys-base-counter-processed');

        const valueElement = block.querySelector('[data-counter-value]');
        const buttons = block.querySelectorAll('[data-counter-action]');

        // Load saved value from localStorage.
        const storageKey = 'ys_counter_value';
        let currentValue = parseInt(localStorage.getItem(storageKey), 10) || 0;
        valueElement.textContent = currentValue;

        // Add event listeners to buttons.
        buttons.forEach(function processButton(button) {
          button.addEventListener('click', function handleClick() {
            const action = this.getAttribute('data-counter-action');

            switch (action) {
              case 'increment':
                currentValue += 1;
                break;
              case 'decrement':
                currentValue -= 1;
                break;
              case 'reset':
                currentValue = 0;
                break;
              default:
                // No action for unknown action types.
                break;
            }

            // Update display
            valueElement.textContent = currentValue;

            // Save to localStorage.
            localStorage.setItem(storageKey, currentValue.toString());

            // Add visual feedback.
            valueElement.classList.add('updated');
            Drupal.behaviors.ysBase.removeUpdatedClassAfterDelay(valueElement);

            // Log action for debugging.
            // eslint-disable-next-line no-console
            console.log(`Counter ${action}: ${currentValue}`);
          });
        });
      });
    },

    /**
     * Remove updated class after a delay for visual feedback.
     *
     * @param {HTMLElement} element
     *   The element to remove the class from.
     */
    removeUpdatedClassAfterDelay(element) {
      setTimeout(function removeUpdatedClass() {
        element.classList.remove('updated');
      }, 300);
    },
  };
})(Drupal);
