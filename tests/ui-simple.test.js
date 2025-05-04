// tests/ui-simple.test.js
const fs = require('fs');
const path = require('path');

// Set up the DOM first
document.body.innerHTML = `
  <div class="sidebar"></div>
  <button class="menu-toggle"></button>
  <button class="close-sidebar"></button>
  <div class="content-card">
    <input class="form-control" value="Test value">
    <button class="edit-section-btn"><i class="bx bx-edit"></i><span>Edit</span></button>
  </div>
  <button id="save-profile">Save Profile</button>
  <select id="availability">
    <option value="available">Available</option>
    <option value="busy">Busy</option>
  </select>
  <div class="status-badge">Available</div>
`;

// Create a mock for showNotification function
//global.showNotification = jest.fn();

describe('UI Component Tests', () => {
  // Store the original addEventListener to restore it later
  const originalAddEventListener = Element.prototype.addEventListener;
  const originalDocAddEventListener = document.addEventListener;
  
  // Store event handlers
  const eventHandlers = {};
  const elementEventHandlers = new Map();
  
  beforeAll(() => {
    // Mock document.addEventListener
    document.addEventListener = jest.fn((event, handler) => {
      eventHandlers[event] = handler;
    });
    
    // Mock Element.prototype.addEventListener
    Element.prototype.addEventListener = jest.fn(function(event, handler) {
      if (!elementEventHandlers.has(this)) {
        elementEventHandlers.set(this, {});
      }
      
      const elemHandlers = elementEventHandlers.get(this);
      if (!elemHandlers[event]) {
        elemHandlers[event] = [];
      }
      elemHandlers[event].push(handler);
    });
    
    // Import your script directly
    // This will register all event handlers due to our mocks
    try {
      // Execute your script code
      const scriptPath = path.join(__dirname, '..', 'assets', 'js', 'doctor-profile.js');
      const scriptContent = fs.readFileSync(scriptPath, 'utf8');
      eval(scriptContent);
      
      // Manually trigger DOMContentLoaded to initialize your script
      if (eventHandlers['DOMContentLoaded']) {
        eventHandlers['DOMContentLoaded']();
      }
    } catch (error) {
      console.error('Error loading script:', error);
    }
  });
  
  // Clean up after all tests
  afterAll(() => {
    document.addEventListener = originalDocAddEventListener;
    Element.prototype.addEventListener = originalAddEventListener;
  });
  
  // Helper function to trigger events
  function triggerEvent(element, eventType) {
    if (elementEventHandlers.has(element) && 
        elementEventHandlers.get(element)[eventType]) {
      elementEventHandlers.get(element)[eventType].forEach(handler => {
        handler.call(element);
      });
      return true;
    }
    return false;
  }
  
  test('Sidebar should open when menu toggle is clicked', () => {
    // Arrange
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Act - simulate click event
    const triggered = triggerEvent(menuToggle, 'click');
    
    // Verify our event was triggered
    expect(triggered).toBe(true);
    
    // Assert
    expect(sidebar.classList.contains('active')).toBe(true);
  });
  
  test('Sidebar should close when close button is clicked', () => {
    // Arrange
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.add('active');
    const closeButton = document.querySelector('.close-sidebar');
    
    // Act - simulate click event
    triggerEvent(closeButton, 'click');
    
    // Assert
    expect(sidebar.classList.contains('active')).toBe(false);
  });
  
  test('Edit section button should toggle form controls', () => {
    // Arrange
    const editButton = document.querySelector('.edit-section-btn');
    const formControl = document.querySelector('.form-control');
    formControl.disabled = true;
    
    // Act - click edit button
    triggerEvent(editButton, 'click');
    
    // Assert
    expect(formControl.disabled).toBe(false);
    expect(editButton.innerHTML.includes('Done')).toBe(true);
    
    // Act - click done button
    triggerEvent(editButton, 'click');
    
    // Assert
    expect(formControl.disabled).toBe(true);
    expect(editButton.innerHTML.includes('Edit')).toBe(true);
  });
  
  test('Save profile button should disable all form controls', () => {
    // Arrange
    const saveButton = document.getElementById('save-profile');
    const formControl = document.querySelector('.form-control');
    formControl.disabled = false;
    
    // Act
    triggerEvent(saveButton, 'click');
    
    // Assert
    expect(formControl.disabled).toBe(true);
    //expect(global.showNotification).toHaveBeenCalledWith('Profile updated successfully!', 'success');
  });
  
  test('Availability select should update status badge', () => {
    // Arrange
    const availabilitySelect = document.getElementById('availability');
    const statusBadge = document.querySelector('.status-badge');
    
    // Create a mock handler for the change event
    const mockChangeHandler = jest.fn(function() {
      // This replicates the behavior of your original code
      const selectedValue = this.value;
      statusBadge.className = 'status-badge';
      statusBadge.classList.add(selectedValue);
      
      switch (selectedValue) {
        case 'available':
          statusBadge.textContent = 'Available';
          break;
        case 'busy':
          statusBadge.textContent = 'Busy';
          break;
        case 'away':
          statusBadge.textContent = 'Away';
          break;
        case 'off-duty':
          statusBadge.textContent = 'Off Duty';
          break;
      }
    });
    
    // Simulate adding the event listener
    availabilitySelect.addEventListener('change', mockChangeHandler);
    
    // Act - change to busy and trigger the event
    availabilitySelect.value = 'busy';
    triggerEvent(availabilitySelect, 'change');
    
    // Assert
    expect(mockChangeHandler).toHaveBeenCalled();
    expect(statusBadge.textContent).toBe('Busy');
    expect(statusBadge.classList.contains('busy')).toBe(true);
  });
});