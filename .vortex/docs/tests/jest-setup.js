import '@testing-library/jest-dom';

// Polyfill for EventTarget in jsdom environment
global.EventTarget = class EventTarget {
  constructor() {
    this.listeners = {};
  }
  
  addEventListener(type, listener) {
    if (!this.listeners[type]) {
      this.listeners[type] = [];
    }
    this.listeners[type].push(listener);
  }
  
  removeEventListener(type, listener) {
    if (this.listeners[type]) {
      this.listeners[type] = this.listeners[type].filter(l => l !== listener);
    }
  }
  
  dispatchEvent(event) {
    if (this.listeners[event.type]) {
      this.listeners[event.type].forEach(listener => listener(event));
    }
    return true;
  }
};

// Mock MDX components that might be used in tests
global.React = require('react');
