/*
 * YS Demo module JavaScript behaviors.
 */

((Drupal) => {
  Drupal.behaviors.ysDemo = {
    storageKey: 'ys_counter_value',

    attach(context) {
      this.initCounterBlock(context);
    },

    /**
     * Counter block functionality.
     *
     * @param {HTMLElement} context  Context element to search for counter
     *                               blocks.
     */
    initCounterBlock(context) {
      const counterBlocks = context.querySelectorAll('[data-the-force-demo-counter]');

      counterBlocks.forEach((block) => {
        // Skip if already processed.
        if (block.classList.contains('the-force-demo-counter-processed')) {
          return;
        }

        block.classList.add('the-force-demo-counter-processed');

        const valueElement = block.querySelector('[data-counter-value]');
        const buttons = block.querySelectorAll('[data-counter-action]');

        // Load saved value from localStorage.
        let currentValue = this.getCounterValue();
        valueElement.textContent = currentValue;

        // Add event listeners to buttons.
        buttons.forEach((button) => {
          button.addEventListener('click', () => {
            const action = button.getAttribute('data-counter-action');
            currentValue = this.applyAction(currentValue, action);

            // Update display.
            valueElement.textContent = currentValue;

            // Save to localStorage.
            localStorage.setItem(this.storageKey, currentValue.toString());

            // Add visual feedback.
            valueElement.classList.add('updated');
            this.removeUpdatedClassAfterDelay(valueElement);

            // Log action for debugging.
            // eslint-disable-next-line no-console
            console.log(`Counter ${action}: ${currentValue}`);
          });
        });
      });
    },

    /**
     * Apply a counter action and return the new value.
     *
     * @param {number} value   The current counter value.
     * @param {string} action  The action to apply.
     * @return {number} The new counter value.
     */
    applyAction(value, action) {
      switch (action) {
        case 'increment':
          return value + 1;
        case 'decrement':
          return value - 1;
        case 'reset':
          return 0;
        default:
          return value;
      }
    },

    /**
     * Remove updated class after a delay for visual feedback.
     *
     * @param {HTMLElement} element  The element to remove the class from.
     */
    removeUpdatedClassAfterDelay(element) {
      setTimeout(function removeUpdatedClass() {
        element.classList.remove('updated');
      }, 300);
    },

    /**
     * Get the current counter value from localStorage.
     *
     * @return {number} The current counter value, or 0 if not set.
     */
    getCounterValue() {
      return parseInt(localStorage.getItem(this.storageKey), 10) || 0;
    },
  };
})(Drupal);
