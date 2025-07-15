/**
 * @file
 * YS Base module JavaScript behaviors.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.ysBase = {
    attach: function (context) {
      this.initCounterBlock(context);
    },

    /**
     * Counter block functionality.
     *
     * @param {HTMLElement} context
     *   Context element to search for counter blocks.
     */
    initCounterBlock: function (context) {
      const counterBlocks = context.querySelectorAll('[data-ys-base-counter]');

      counterBlocks.forEach(function (block) {
        // Skip if already processed.
        if (block.classList.contains('ys-base-counter-processed')) {
          return;
        }

        block.classList.add('ys-base-counter-processed');

        const valueElement = block.querySelector('[data-counter-value]');
        const buttons = block.querySelectorAll('[data-counter-action]');

        // Load saved value from localStorage.
        const storageKey = 'ys_counter_value';
        let currentValue = parseInt(localStorage.getItem(storageKey)) || 0;
        valueElement.textContent = currentValue;

        // Add event listeners to buttons.
        buttons.forEach(function (button) {
          button.addEventListener('click', function () {
            const action = this.getAttribute('data-counter-action');

            switch (action) {
              case 'increment':
                currentValue++;
                break;
              case 'decrement':
                currentValue--;
                break;
              case 'reset':
                currentValue = 0;
                break;
            }

            // Update display
            valueElement.textContent = currentValue;

            // Save to localStorage.
            localStorage.setItem(storageKey, currentValue.toString());

            // Add visual feedback.
            valueElement.classList.add('updated');
            setTimeout(function () {
              valueElement.classList.remove('updated');
            }, 300);

            // Log action.
            console.log('Counter ' + action + ': ' + currentValue);
          });
        });
      });
    }
  };

})(Drupal);
