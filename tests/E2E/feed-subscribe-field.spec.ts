import { expect, test } from '@playwright/test';
import { createForm, deleteForm, savedFeed, SUBSCRIBE_FIELD_ID, themeWithChildCategory } from './support/wp';

/**
 * A form with one subscribe checkbox has no Subscribe checkbox select in its feed: the feed stores that checkbox
 * itself (app/Addon.php, subscribe_field_settings()).
 */
test.describe('Feed subscribe checkbox', () => {
    let formId: number;

    test.afterEach(() => {
        if (formId) {
            deleteForm(formId);
        }
    });

    test('stores the only subscribe checkbox without asking', async ({ page }) => {
        const choice = themeWithChildCategory();
        test.skip(!choice, 'No cached Wereldkundig theme with categories; save the Wereldkundig settings on the test site first.');
        formId = createForm(1);

        await page.goto(`wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=wereldkundig&id=${formId}&fid=0`);
        await expect(page.getByText('Nieuwsbrief (field 2), the only subscribe checkbox of this form.')).toBeVisible();
        await expect(page.locator('select[name="_gform_setting_subscribe_field"]')).toHaveCount(0);

        await page.locator(`.wk-theme-checkbox[name$="[${choice!.theme}][theme]"]`).check();
        await Promise.all([page.waitForLoadState('load'), page.locator('#gform-settings-save').click()]);

        expect(savedFeed(formId)).toMatchObject({ subscribe_field: String(SUBSCRIBE_FIELD_ID), wk_themes: { [choice!.theme]: [] } });
    });
});
