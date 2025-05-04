// tests/ui-simple.test.js

// Mock document object before importing the code
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

// Mock addEventListener
let domEventListeners = {};
document.addEventListener = jest.fn((event, handler) => {
  domEventListeners[event] = handler;
});

// Store original method
const originalAddEventListener = Element.prototype.addEventListener;

// Mock element addEventListener
Element.prototype.addEventListener = jest.fn(function(event, handler) {
  const element = this;
  originalAddEventListener.call(element, event, function(e) {
    // Call the handler with the element as 'this'
    handler.call(element, e);
  });
});

// Create a show notification mock
global.showNotification = jest.fn();

describe('UI Component Tests', () => {
  beforeEach(() => {
    // Clear any mocks
    jest.clearAllMocks();
    
    // Reset the DOM
    document.querySelector('.sidebar').classList.remove('active');
    document.querySelector('.form-control').disabled = true;
    document.querySelector('.edit-section-btn').innerHTML = '<i class="bx bx-edit"></i><span>Edit</span>';
    document.querySelector('.status-badge').textContent = 'Available';
    document.querySelector('.status-badge').className = 'status-badge available';
  });
  
  // Trigger the DOMContentLoaded event to initialize our scripts
  beforeAll(() => {
    if (domEventListeners['DOMContentLoaded']) {
      domEventListeners['DOMContentLoaded']();
    }
  });
  
  test('Sidebar should open when menu toggle is clicked', () => {
    // Arrange
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    // Act - simulate click event
    menuToggle.click();
    
    // Assert
    expect(sidebar.classList.contains('active')).toBe(true);
  });
  
  test('Sidebar should close when close button is clicked', () => {
    // Arrange
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.add('active');
    const closeButton = document.querySelector('.close-sidebar');
    
    // Act - simulate click event
    closeButton.click();
    
    // Assert
    expect(sidebar.classList.contains('active')).toBe(false);
  });
  
  test('Edit section button should toggle form controls', () => {
    // Arrange
    const editButton = document.querySelector('.edit-section-btn');
    const formControl = document.querySelector('.form-control');
    
    // Act - click edit button
    editButton.click();
    
    // Assert
    expect(formControl.disabled).toBe(false);
    expect(editButton.innerHTML).toContain('Done');
    
    // Act - click done button
    editButton.click();
    
    // Assert
    expect(formControl.disabled).toBe(true);
    expect(editButton.innerHTML).toContain('Edit');
  });
  
  test('Save profile button should disable all form controls', () => {
    // Arrange
    const saveButton = document.getElementById('save-profile');
    const formControl = document.querySelector('.form-control');
    formControl.disabled = false;
    
    // Act
    saveButton.click();
    
    // Assert
    expect(formControl.disabled).toBe(true);
    expect(global.showNotification).toHaveBeenCalledWith('Profile updated successfully!', 'success');
  });
  
  test('Availability select should update status badge', () => {
    // Arrange
    const availabilitySelect = document.getElementById('availability');
    const statusBadge = document.querySelector('.status-badge');
    availabilitySelect.value = 'available';
    
    // Act - change to busy
    availabilitySelect.value = 'busy';
    availabilitySelect.dispatchEvent(new Event('change'));
    
    // Assert
    expect(statusBadge.textContent).toBe('Busy');
    expect(statusBadge.classList.contains('busy')).toBe(true);
  });
});