import { expect, afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';

import { server } from '../mocks/server.js';

// Her testten önce mock sunucusunu başlat
beforeAll(() => server.listen());

// Her testten sonra DOM'u temizle ve mock sunucusunu sıfırla
afterEach(() => {
  cleanup();
  server.resetHandlers();
});

// Tüm testler bittikten sonra mock sunucusunu kapat
afterAll(() => server.close());
