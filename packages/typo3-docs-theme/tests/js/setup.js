import '@testing-library/jest-dom/vitest';

// jsdom does not implement these but the SearchModal calls them.
HTMLElement.prototype.scrollIntoView = function () {};
