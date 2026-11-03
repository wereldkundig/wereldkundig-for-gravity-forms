import { expect, test, type Page } from '@playwright/test';
import {
    createForm,
    deleteForm,
    themeWithChildCategory,
    savedField,
    SUBSCRIBE_FIELD_ID,
    type ThemeWithCategories,
} from './support/wp';

/**
 * The theme and category checkboxes of the subscribe field in the form editor, saved with Save Form.
 * The selection is stored by wkSetFieldThemes() (app/SubscribeField.php), which only a browser runs.
 */
test.describe.serial('Subscribe field editor', () => {
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

    const themeCheckbox = (page: Page) => page.locator(`.wk-theme-checkbox[value="${choice.theme}"]`);
    const categoryCheckbox = (page: Page, category: string) => page.locator(`.wk-category-checkbox[value="${category}"]`);
    // Gravity Forms styles the settings checkboxes so the label takes the click, as it does for a user.
    const labelOf = (page: Page, checkbox: string) => page.locator(`${checkbox} + label`);
    const themeLabel = (page: Page) => labelOf(page, `.wk-theme-checkbox[value="${choice.theme}"]`);
    const categoryLabel = (page: Page, category: string) => labelOf(page, `.wk-category-checkbox[value="${category}"]`);

    async function openField(page: Page): Promise<void> {
        await page.goto(`wp-admin/admin.php?page=gf_edit_forms&id=${formId}`);
        await page.locator(`#field_${SUBSCRIBE_FIELD_ID}`).click();
        await expect(themeCheckbox(page)).toBeVisible();
    }

    async function saveForm(page: Page): Promise<void> {
        const saved = page.waitForResponse(
            (response) => response.url().includes('admin-ajax.php') && (response.request().postData() ?? '').includes('form_editor_save_form'),
        );
        await page.locator('button.update-form').click();
        expect((await saved).ok()).toBe(true);
    }

    test('saves a checked category', async ({ page }) => {
        await openField(page);
        await themeLabel(page).check();
        await categoryLabel(page, choice.category).check();
        await saveForm(page);

        expect(savedField(formId)).toEqual({ wk_theme_ids: [choice.theme], wk_category_ids: [choice.category] });

        // After a reload the editor shows the stored selection again.
        await openField(page);
        await expect(themeCheckbox(page)).toBeChecked();
        await expect(categoryCheckbox(page, choice.category)).toBeChecked();
        await expect(categoryCheckbox(page, choice.category)).toBeEnabled();
        await expect(categoryCheckbox(page, choice.selectAll)).not.toBeChecked();
    });

    test('unchecking the theme drops its categories', async ({ page }) => {
        await openField(page);
        await themeLabel(page).uncheck();
        await expect(categoryCheckbox(page, choice.category)).toBeDisabled();
        await saveForm(page);

        expect(savedField(formId)).toEqual({ wk_theme_ids: [], wk_category_ids: [] });
    });

    test('"Alles in …" is stored as select-all', async ({ page }) => {
        await openField(page);
        await themeLabel(page).check();
        await categoryLabel(page, choice.selectAll).check();
        await saveForm(page);

        expect(savedField(formId)).toEqual({ wk_theme_ids: [choice.theme], wk_category_ids: [choice.selectAll] });
    });
});
