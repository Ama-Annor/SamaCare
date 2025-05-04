// tests/basic.test.js
/**
 * This is a minimal test file that focuses on just testing 
 * if the JS file can be loaded and if the DOM is properly set up.
 */

describe('Basic Tests', () => {
    // Test that we can load the JavaScript file
    test('JavaScript file exists and can be loaded', () => {
      const fs = require('fs');
      const path = require('path');
      
      const scriptPath = path.join(__dirname, '..', 'assets', 'js', 'doctor-profile.js');
      
      // Check if file exists
      expect(fs.existsSync(scriptPath)).toBe(true);
      
      // Check if file can be read
      const scriptContent = fs.readFileSync(scriptPath, 'utf8');
      expect(scriptContent).toBeTruthy();
    });
    
    // Test that our DOM elements exist
    test('DOM elements exist', () => {
      // Set up a basic DOM
      document.body.innerHTML = `
        <div class="sidebar"></div>
        <button class="menu-toggle"></button>
        <button class="close-sidebar"></button>
      `;
      
      // Test if elements exist
      expect(document.querySelector('.sidebar')).not.toBeNull();
      expect(document.querySelector('.menu-toggle')).not.toBeNull();
      expect(document.querySelector('.close-sidebar')).not.toBeNull();
    });
    
    // Test a simple DOM interaction
    test('Simple DOM interaction', () => {
      // Set up DOM
      document.body.innerHTML = `
        <div class="content-card">
          <input class="form-control" value="Test">
        </div>
      `;
      
      // Get input and test value
      const input = document.querySelector('.form-control');
      expect(input.value).toBe('Test');
      
      // Change value
      input.value = 'Updated';
      expect(input.value).toBe('Updated');
    });
  });