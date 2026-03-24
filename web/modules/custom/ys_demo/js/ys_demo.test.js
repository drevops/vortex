/**
 * @jest-environment jsdom
 */

const fs = require('fs');
const path = require('path');

describe('Drupal.behaviors.ysDemo', () => {
  beforeEach(() => {
    localStorage.clear();
    global.Drupal = { behaviors: {} };

    const filePath = path.resolve(__dirname, 'ys_demo.js');
    const code = fs.readFileSync(filePath, 'utf8');
    eval(code);
  });

  afterEach(() => {
    delete global.Drupal;
  });

  function createCounterBlockHtml() {
    return `
      <div data-ys-demo-counter>
        <span data-counter-value>0</span>
        <button data-counter-action="increment">+</button>
        <button data-counter-action="decrement">-</button>
        <button data-counter-action="reset">Reset</button>
      </div>
    `;
  }

  describe('storageKey', () => {
    it('should have the expected storage key', () => {
      expect(Drupal.behaviors.ysDemo.storageKey).toBe('ys_counter_value');
    });
  });

  describe('getCounterValue', () => {
    it('should return 0 when localStorage is empty', () => {
      expect(Drupal.behaviors.ysDemo.getCounterValue()).toBe(0);
    });

    it('should return the stored value from localStorage', () => {
      localStorage.setItem('ys_counter_value', '42');
      expect(Drupal.behaviors.ysDemo.getCounterValue()).toBe(42);
    });

    it('should return 0 for non-numeric localStorage values', () => {
      localStorage.setItem('ys_counter_value', 'invalid');
      expect(Drupal.behaviors.ysDemo.getCounterValue()).toBe(0);
    });

    it('should return negative values correctly', () => {
      localStorage.setItem('ys_counter_value', '-5');
      expect(Drupal.behaviors.ysDemo.getCounterValue()).toBe(-5);
    });
  });

  describe('applyAction', () => {
    it('should increment the value', () => {
      expect(Drupal.behaviors.ysDemo.applyAction(0, 'increment')).toBe(1);
      expect(Drupal.behaviors.ysDemo.applyAction(10, 'increment')).toBe(11);
    });

    it('should decrement the value', () => {
      expect(Drupal.behaviors.ysDemo.applyAction(0, 'decrement')).toBe(-1);
      expect(Drupal.behaviors.ysDemo.applyAction(5, 'decrement')).toBe(4);
    });

    it('should reset the value to zero', () => {
      expect(Drupal.behaviors.ysDemo.applyAction(42, 'reset')).toBe(0);
      expect(Drupal.behaviors.ysDemo.applyAction(-5, 'reset')).toBe(0);
    });

    it('should return the same value for unknown actions', () => {
      expect(Drupal.behaviors.ysDemo.applyAction(7, 'unknown')).toBe(7);
    });
  });

  describe('removeUpdatedClassAfterDelay', () => {
    beforeEach(() => {
      jest.useFakeTimers();
    });

    afterEach(() => {
      jest.useRealTimers();
    });

    it('should remove the updated class after 300ms', () => {
      document.body.innerHTML = '<span class="updated"></span>';
      const element = document.querySelector('span');

      Drupal.behaviors.ysDemo.removeUpdatedClassAfterDelay(element);

      expect(element.classList.contains('updated')).toBe(true);
      jest.advanceTimersByTime(300);
      expect(element.classList.contains('updated')).toBe(false);
    });

    it('should not remove the class before 300ms', () => {
      document.body.innerHTML = '<span class="updated"></span>';
      const element = document.querySelector('span');

      Drupal.behaviors.ysDemo.removeUpdatedClassAfterDelay(element);

      jest.advanceTimersByTime(299);
      expect(element.classList.contains('updated')).toBe(true);
    });
  });

  describe('initCounterBlock', () => {
    it('should initialize the counter display with 0', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('0');
    });

    it('should initialize with saved localStorage value', () => {
      localStorage.setItem('ys_counter_value', '15');
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('15');
    });

    it('should mark the block as processed', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const block = document.querySelector('[data-ys-demo-counter]');
      expect(block.classList.contains('ys-demo-counter-processed')).toBe(true);
    });

    it('should not re-process already processed blocks', () => {
      document.body.innerHTML = createCounterBlockHtml();
      const block = document.querySelector('[data-ys-demo-counter]');
      block.classList.add('ys-demo-counter-processed');

      const value = document.querySelector('[data-counter-value]');
      value.textContent = 'original';

      Drupal.behaviors.ysDemo.initCounterBlock(document);

      expect(value.textContent).toBe('original');
    });

    it('should increment counter on button click', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const incrementBtn = document.querySelector(
        '[data-counter-action="increment"]',
      );
      incrementBtn.click();

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('1');
    });

    it('should decrement counter on button click', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const decrementBtn = document.querySelector(
        '[data-counter-action="decrement"]',
      );
      decrementBtn.click();

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('-1');
    });

    it('should reset counter on button click', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const incrementBtn = document.querySelector(
        '[data-counter-action="increment"]',
      );
      incrementBtn.click();
      incrementBtn.click();
      incrementBtn.click();

      const resetBtn = document.querySelector('[data-counter-action="reset"]');
      resetBtn.click();

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('0');
    });

    it('should save counter value to localStorage on click', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const incrementBtn = document.querySelector(
        '[data-counter-action="increment"]',
      );
      incrementBtn.click();
      incrementBtn.click();

      expect(localStorage.getItem('ys_counter_value')).toBe('2');
    });

    it('should add updated class on click for visual feedback', () => {
      jest.useFakeTimers();
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const incrementBtn = document.querySelector(
        '[data-counter-action="increment"]',
      );
      incrementBtn.click();

      const value = document.querySelector('[data-counter-value]');
      expect(value.classList.contains('updated')).toBe(true);

      jest.advanceTimersByTime(300);
      expect(value.classList.contains('updated')).toBe(false);
      jest.useRealTimers();
    });

    it('should handle multiple counter blocks', () => {
      document.body.innerHTML = `
        <div data-ys-demo-counter>
          <span data-counter-value>0</span>
          <button data-counter-action="increment">+</button>
        </div>
        <div data-ys-demo-counter>
          <span data-counter-value>0</span>
          <button data-counter-action="increment">+</button>
        </div>
      `;
      Drupal.behaviors.ysDemo.initCounterBlock(document);

      const blocks = document.querySelectorAll('[data-ys-demo-counter]');
      expect(blocks[0].classList.contains('ys-demo-counter-processed')).toBe(
        true,
      );
      expect(blocks[1].classList.contains('ys-demo-counter-processed')).toBe(
        true,
      );
    });
  });

  describe('attach', () => {
    it('should call initCounterBlock with the context', () => {
      document.body.innerHTML = createCounterBlockHtml();
      const spy = jest.spyOn(Drupal.behaviors.ysDemo, 'initCounterBlock');

      Drupal.behaviors.ysDemo.attach(document);

      expect(spy).toHaveBeenCalledWith(document);
      spy.mockRestore();
    });

    it('should fully initialize when called as a behavior', () => {
      document.body.innerHTML = createCounterBlockHtml();
      Drupal.behaviors.ysDemo.attach(document);

      const incrementBtn = document.querySelector(
        '[data-counter-action="increment"]',
      );
      incrementBtn.click();

      const value = document.querySelector('[data-counter-value]');
      expect(value.textContent).toBe('1');
      expect(localStorage.getItem('ys_counter_value')).toBe('1');
    });
  });
});
