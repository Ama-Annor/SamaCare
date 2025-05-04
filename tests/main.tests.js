// tests/main.test.js
const fs = require('fs');
const path = require('path');

// Import the script content
const scriptPath = path.join(__dirname, '..', 'assets', 'js', 'doctor-profile.js'); // Path to your script file
const scriptContent = fs.readFileSync(scriptPath, 'utf8');

// Mock the DOM environment
document.body.innerHTML = `
  <div class="sidebar"></div>
  <div class="main-content"></div>
  <button class="menu-toggle"></button>
  <button class="close-sidebar"></button>
  <div class="content-card">
    <input class="form-control" value="Test value">
    <button class="edit-section-btn"><i class="bx bx-edit"></i><span>Edit</span></button>
  </div>
  <button id="save-profile">Save Profile</button>
  <button id="cancel-edit">Cancel</button>
  <div class="schedule-day">
    <label class="day-label">
      <input type="checkbox">
      <div class="time-slots">
        <input type="time">
      </div>
    </label>
  </div>
  <div class="status-text">Status</div>
  <div class="status-badge">Available</div>
  <select id="availability">
    <option value="available">Available</option>
    <option value="busy">Busy</option>
    <option value="away">Away</option>
    <option value="off-duty">Off Duty</option>
  </select>
  <div class="edit-avatar"></div>
`;

// Create a script element to run the code
const script = document.createElement('script');
script.textContent = scriptContent;

// Manually trigger DOMContentLoaded
let eventHandlers = {};

// Mock event listener
document.addEventListener = jest.fn((event, handler) => {
  eventHandlers[event] = handler;
});

// Mock DOM elements' addEventListener
const originalAddEventListener = Element.prototype.addEventListener;
Element.prototype.addEventListener = jest.fn(function(event, handler) {
  if (!this._eventHandlers) this._eventHandlers = {};
  if (!this._eventHandlers[event]) this._eventHandlers[event] = [];
  this._eventHandlers[event].push(handler);
  return originalAddEventListener.call(this, event, handler);
});

// Helper function to trigger events
function triggerEvent(element, eventType) {
  if (element._eventHandlers && element._eventHandlers[eventType]) {
    element._eventHandlers[eventType].forEach(handler => handler.call(element));
  }
}

describe('UI Functionality Tests', () => {
  // Run the script to set up event listeners
  beforeAll(() => {
    // Trigger the DOMContentLoaded event handler
    if (eventHandlers['DOMContentLoaded']) {
      eventHandlers['DOMContentLoaded']();
    }
  });

  test('Sidebar toggle functionality', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Initially sidebar should not have active class
    expect(sidebar.classList.contains('active')).toBe(false);
    
    // Trigger menu toggle click
    triggerEvent(menuToggle, 'click');
    
    // Sidebar should now have active class
    expect(sidebar.classList.contains('active')).toBe(true);
    
    // Trigger close sidebar click
    const closeSidebar = document.querySelector('.close-sidebar');
    triggerEvent(closeSidebar, 'click');
    
    // Sidebar should no longer have active class
    expect(sidebar.classList.contains('active')).toBe(false);
  });

  test('Edit section functionality', () => {
    const editButton = document.querySelector('.edit-section-btn');
    const input = document.querySelector('.form-control');
    
    // Input should be disabled initially
    expect(input.disabled).toBe(true);
    
    // Click edit button
    triggerEvent(editButton, 'click');
    
    // Input should now be enabled
    expect(input.disabled).toBe(false);
    
    // Button text should change
    expect(editButton.innerHTML).toContain('Done');
    
    // Click again to save
    triggerEvent(editButton, 'click');
    
    // Input should be disabled again
    expect(input.disabled).toBe(true);
    
    // Button text should change back
    expect(editButton.innerHTML).toContain('Edit');
  });

  test('Schedule day checkbox toggle', () => {
    const checkbox = document.querySelector('.day-label input[type="checkbox"]');
    const timeSlots = document.querySelector('.time-slots');
    const timeInput = timeSlots.querySelector('input');
    
    // Initially checkbox is unchecked
    checkbox.checked = false;
    
    // Trigger change event
    triggerEvent(checkbox, 'change');
    
    // Time slots should be disabled
    expect(timeSlots.classList.contains('disabled')).toBe(true);
    expect(timeInput.disabled).toBe(true);
    
    // Check the checkbox
    checkbox.checked = true;
    
    // Trigger change event
    triggerEvent(checkbox, 'change');
    
    // Time slots should be enabled
    expect(timeSlots.classList.contains('disabled')).toBe(false);
    expect(timeInput.disabled).toBe(false);
  });

  test('Availability select change', () => {
    const availabilitySelect = document.getElementById('availability');
    const statusBadge = document.querySelector('.status-badge');
    
    // Change selection to "busy"
    availabilitySelect.value = 'busy';
    
    // Trigger change event
    triggerEvent(availabilitySelect, 'change');
    
    // Badge should update
    expect(statusBadge.textContent).toBe('Busy');
    expect(statusBadge.classList.contains('busy')).toBe(true);
    
    // Change selection to "away"
    availabilitySelect.value = 'away';
    
    // Trigger change event
    triggerEvent(availabilitySelect, 'change');
    
    // Badge should update
    expect(statusBadge.textContent).toBe('Away');
    expect(statusBadge.classList.contains('away')).toBe(true);
  });
});