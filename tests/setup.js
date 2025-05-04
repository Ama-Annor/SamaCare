// tests/setup.js

// Mock browser globals that might be missing in Jest JSDOM environment
window.matchMedia = window.matchMedia || function() {
    return {
      matches: false,
      addListener: function() {},
      removeListener: function() {}
    };
  };
  
  // Create any missing DOM elements for your tests
  document.body.innerHTML = document.body.innerHTML || '';
  
  // Set up box-icons mock if you're using them in your code
  global.BoxIconElement = class BoxIconElement extends HTMLElement {
    constructor() {
      super();
    }
  };
  customElements.define('box-icon', global.BoxIconElement);
  
  // Mock any browser APIs that your code uses but Jest doesn't provide
  global.showNotification = global.showNotification || jest.fn();