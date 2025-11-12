import { setupServer } from 'msw/node';
import { handlers } from './handlers';

// Bu, test sunucusunu sağlanan handler'lar ile yapılandırır.
export const server = setupServer(...handlers);
