import { test, expect } from '@playwright/test';

test.describe('Pharmacy Prescription', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('http://localhost:8000/pharmacie');
    });

    test('should save prescription when clicking Enregistrer', async ({ page }) => {
        // Open new prescription form
        await page.getByRole('button', { name: 'Nouvelle ordonnance' }).click();
        await page.waitForTimeout(500);

        // Select patient
        const patientInput = page.getByPlaceholder('Rechercher par CSM-XXXX ou par nom...');
        await patientInput.fill('CSM-4025');
        await page.waitForTimeout(1000);

        // Click first patient option
        await page.locator('span[x-text="option.name"]').first().click();
        await page.waitForTimeout(300);

        // Verify patient selected
        const patientSelected = page.locator('text=Patient sélectionné');
        await expect(patientSelected).toBeVisible();

        // Select medication
        await page.getByPlaceholder('Rechercher un médicament...').fill('Fer');
        await page.waitForTimeout(1000);

        // Click first medication option
        await page.locator('text=Fer + Acide folique').first().click();
        await page.waitForTimeout(300);

        // Click + button to add item
        await page.getByRole('button', { name: '+' }).click();
        await page.waitForTimeout(500);

        // Verify item was added (check the item list shows medication name)
        const itemAdded = page.locator('text=Fer + Acide folique');
        await expect(itemAdded).toBeVisible();

        // Submit form
        await page.getByRole('button', { name: 'Enregistrer l\'ordonnance' }).click();

        // Wait for redirect
        await page.waitForURL('**/pharmacie', { timeout: 5000 });

        // Check for success message
        const successMessage = page.locator('text=Ordonnance enregistrée');
        await expect(successMessage).toBeVisible({ timeout: 3000 });

        // Verify prescription was created in database (check list shows the new prescription)
        await page.waitForTimeout(500);
        const prescriptionInList = page.locator('text=CSM-4025').first();
        await expect(prescriptionInList).toBeVisible();
    });
});
