// jest.config.js
module.exports = {
    // The test environment that will be used for testing
    testEnvironment: 'jsdom',
    
    // The root directory where Jest should scan for tests
    roots: ['<rootDir>/tests'],
    
    // The pattern Jest uses to detect test files
    testMatch: ['**/*.test.js'],
    
    // Setup files to run before each test
    setupFiles: ['<rootDir>/tests/setup.js'],
    
    // Ignore these directories when looking for tests
    testPathIgnorePatterns: ['/node_modules/'],
    
    // Collect coverage information
    collectCoverage: true,
    
    // Directory where Jest should output its coverage files
    coverageDirectory: 'coverage',
    
    // Coverage reporting options
    coverageReporters: ['text', 'lcov']
  };