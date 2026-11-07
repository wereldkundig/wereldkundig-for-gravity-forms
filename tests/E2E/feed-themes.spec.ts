import { expect, test, type Page } from '@playwright/test';
import {
    createForm,
    deleteForm,
    OTHER_SUBSCRIBE_FIELD_ID,
    savedFeed,
    SUBSCRIBE_FIELD_ID,
    themeWithChildCategory,
    type ThemeWithCategories,
} from './support/wp';

/**
 * The subscribe checkbox and the theme and category checkboxes of a Wereldkundig feed, saved with Save Settings.
 * The themes are posted as nested inputs (app/ThemesSettingField.php); only a browser shows that the categories of
 * an unchecked theme are disabled, so they're not posted.
 */
test.describe.serial('Feed themes', () => {
    let formId: number;
    let choice: ThemeWithCategories;

    test.beforeAll(() => {
        const found = themeWithChildCategory();
        test.skip(!found, 'No cached Wereldkundig theme with categories; save the Wereldkundig settings on the test site first.');
        choice = found as ThemeWithCategories;
        formId = createForm();
    });

    test.afterAll(() => {
        if (formId) {
            deleteForm(formId);
        }
    });

    const themeCheckbox = (page: Page) => page.locator(`.wk-theme-checkbox[name$="[${choice.theme}][theme]"]`);
    const categoryCheckbox = (page: Page, category: string) => page.locator(`.wk-category-checkbox[data-theme="${choice.theme}"][value="${category}"]`);
    const subscribeField = (page: Page) => page.locator('select[name="_gform_setting_subscribe_field"]');

    async function openFeed(page: Page): Promise<void> {
        const feed = savedFeed(formId);
        await page.goto(`wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=wereldkundig&id=${formId}&fid=${feed ? feed.id : 0}`);
        await expect(themeCheckbox(page)).toBeVisible();
    }

    async function saveFeed(page: Page): Promise<void> {
        await Promise.all([page.waitForLoadState('load'), page.locator('#gform-settings-save').click()]);
    }

    test('requires a subscribe checkbox and a theme', async ({ page }) => {
        await openFeed(page);
        await saveFeed(page);

        await expect(page.locator('.gform-settings-validation__error, .gform-settings-input__container--invalid').first()).toBeVisible();
        expect(savedFeed(formId)).toBeNull();
    });

    test('saves the checkbox, a theme and a checked category', async ({ page }) => {
        await openFeed(page);
        // The categories of an unchecked theme are disabled, so they're not posted.
        await expect(categoryCheckbox(page, choice.category)).toBeDisabled();

        await subscribeField(page).selectOption(String(OTHER_SUBSCRIBE_FIELD_ID));
        await themeCheckbox(page).check();
        await categoryCheckbox(page, choice.category).check();
        await saveFeed(page);

        expect(savedFeed(formId)).toMatchObject({
            subscribe_field: String(OTHER_SUBSCRIBE_FIELD_ID),
            wk_themes: { [choice.theme]: [choice.category] },
        });

        // After a reload the feed shows the stored selection again.
        await openFeed(page);
        await expect(subscribeField(page)).toHaveValue(String(OTHER_SUBSCRIBE_FIELD_ID));
        await expect(themeCheckbox(page)).toBeChecked();
        await expect(categoryCheckbox(page, choice.category)).toBeChecked();
        await expect(categoryCheckbox(page, choice.category)).toBeEnabled();
        await expect(categoryCheckbox(page, choice.selectAll)).not.toBeChecked();
    });

    test('"Alles in …" is stored as select-all', async ({ page }) => {
        await openFeed(page);
        await subscribeField(page).selectOption(String(SUBSCRIBE_FIELD_ID));
        await categoryCheckbox(page, choice.category).uncheck();
        await categoryCheckbox(page, choice.selectAll).check();
        await saveFeed(page);

        expect(savedFeed(formId)).toMatchObject({
            subscribe_field: String(SUBSCRIBE_FIELD_ID),
            wk_themes: { [choice.theme]: [choice.selectAll] },
        });
    });

    test('unchecking the theme drops its categories and keeps the feed from saving without a theme', async ({ page }) => {
        await openFeed(page);
        await themeCheckbox(page).uncheck();
        await expect(categoryCheckbox(page, choice.selectAll)).toBeDisabled();
        await saveFeed(page);

        // Not saved: the stored feed still has its theme.
        expect(savedFeed(formId)?.wk_themes).toEqual({ [choice.theme]: [choice.selectAll] });
    });
});
