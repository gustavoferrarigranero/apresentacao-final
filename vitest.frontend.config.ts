import { defineConfig } from 'vite-plus/test/config';
import { fileURLToPath } from 'node:url';
export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/__tests__/**/*.test.ts'],
        restoreMocks: true,
        clearMocks: true,
    },
});
