import { existsSync } from 'node:fs';
import { defineConfig, devices } from '@playwright/test';

// Same file as the live Pest tests; see .env.local.tests.example.
if (existsSync('.env.local.tests')) {
    process.loadEnvFile('.env.local.tests');
}

export const STORAGE_STATE = 'tests/E2E/.auth/admin.json';

export default defineConfig({
    testDir: 'tests/E2E',
    globalSetup: './tests/E2E/global-setup.ts',
    // The tests change forms on one shared local site.
    workers: 1,
    fullyParallel: false,
    reporter: 'list',
    use: {
        baseURL: process.env.WK_E2E_URL,
        // Herd signs *.test with its own CA.
        ignoreHTTPSErrors: true,
        storageState: STORAGE_STATE,
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
