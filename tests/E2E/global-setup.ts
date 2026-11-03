import { chromium } from '@playwright/test';
import { STORAGE_STATE } from '../../playwright.config';
import { ensureAdmin, siteUrl } from './support/wp';

/** Logs the test admin in once; every test reuses the session. */
export default async function globalSetup(): Promise<void> {
    const { login, password, loginUrl } = ensureAdmin();

    const browser = await chromium.launch();
    const page = await browser.newPage({ ignoreHTTPSErrors: true });

    await page.goto(`${loginUrl}?redirect_to=${encodeURIComponent(new URL('wp-admin/', siteUrl()).toString())}`);
    await page.fill('#user_login', login);
    await page.fill('#user_pass', password);
    await Promise.all([page.waitForURL(/\/wp-admin\//), page.click('#wp-submit')]);

    await page.context().storageState({ path: STORAGE_STATE });
    await browser.close();
}
