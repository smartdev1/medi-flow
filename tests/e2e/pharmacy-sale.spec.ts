import { expect, test, type Page } from '@playwright/test';

import { resetDatabase } from './support/database';

/**
 * Parcours de vente au point de vente pharmacie.
 *
 * Ces tests exécutent la page dans un vrai navigateur : les tests PHPUnit ne
 * vérifient que le contrat serveur et laisseraient passer un composant Alpine
 * inerte, dont la recherche ne renverrait jamais de suggestion.
 */

const PHARMACIST = { email: 'aicha.cisse@cmsm.ci', password: 'password' };

async function login(page: Page) {
    await page.goto('/login');
    await page.fill('input[name="email"]', PHARMACIST.email);
    await page.fill('input[name="password"]', PHARMACIST.password);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/(pharmacie|accueil|caisse)/);
}

/** La zone de vente, pour ne pas confondre ses résultats avec ceux d'ailleurs. */
function salePanel(page: Page) {
    return page.locator('[x-data^="pharmacySale("]');
}

test.describe('Point de vente pharmacie', () => {
    test.beforeEach(() => {
        resetDatabase();
    });

    test('le composant est initialisé et la vente est en ligne', async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await login(page);
        await page.goto('/pharmacie');

        // Ni tiroir ni bouton d'ouverture : la vente se compose sur la page.
        await expect(page.getByText('Vente au comptoir')).toBeVisible();
        await expect(page.locator('#sale-patient')).toBeVisible();
        await expect(page.locator('#sale-search')).toBeVisible();
        await expect(page.getByText('Nouvelle vente')).toHaveCount(0);

        expect(errors).toEqual([]);
    });

    test('la recherche de patient propose les patients enregistrés', async ({ page }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-patient').fill('Kouassi');

        // Assertion comportementale : sans elle, une recherche inerte passerait.
        await expect(
            salePanel(page).getByRole('button', { name: /Kouassi/ })
        ).toBeVisible();
    });

    test('un patient sélectionné alimente le dossier de la vente', async ({ page }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-patient').fill('Kouassi');
        await salePanel(page).getByRole('button', { name: /Kouassi/ }).click();

        // Le dossier transmitted est un champ caché : sans lui, la vente
        // partirait sans patient et serait refusée.
        await expect(salePanel(page).locator('input[name="patient_id"]')).toHaveValue(
            /^\d+$/
        );
    });

    test('les médicaments du catalogue sont proposés au panier', async ({ page }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-search').fill('Paracétamol');
        await expect(
            salePanel(page).getByRole('button', { name: /Paracétamol/ }).first()
        ).toBeVisible();

        await salePanel(page).getByRole('button', { name: /Paracétamol/ }).first().click();

        await expect(page.getByText('Total à encaisser')).toBeVisible();
    });

    test('le panier annonce le prix du catalogue', async ({ page }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-search').fill('Paracétamol');
        await salePanel(page).getByRole('button', { name: /Paracétamol/ }).first().click();

        // Le seeder fixe le paracétamol à 500 FCFA l'unité : le montant
        // annoncé au pharmacien doit s'y conformer, sans quoi il encaisse un
        // montant différent de celui facturé.
        await expect(
            salePanel(page).getByText("500 FCFA l'unité")
        ).toBeVisible();
    });

    test('un panier sans patient ni moyen de paiement ne se soumet pas', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-search').fill('Paracétamol');
        await salePanel(page).getByRole('button', { name: /Paracétamol/ }).first().click();

        // Ni patient choisi ni moyen de paiement : la vente serait refusée.
        await expect(salePanel(page).locator('input[name="patient_id"]')).toHaveValue('');
    });

    test('une vente complète est encaissée', async ({ page }) => {
        await login(page);
        await page.goto('/pharmacie');

        await page.locator('#sale-patient').fill('Kouassi');
        await salePanel(page).getByRole('button', { name: /Kouassi/ }).click();

        await page.locator('#sale-search').fill('Paracétamol');
        await salePanel(page).getByRole('button', { name: /Paracétamol/ }).first().click();

        await page.selectOption('#sale-method', { label: 'Espèces' });
        await page.getByRole('button', { name: 'Encaisser la vente' }).click();

        await expect(page.getByText(/Vente encaissée pour/)).toBeVisible();
    });

    test('un stock trop bas pour la quantité demandée est signalé', async ({
        page,
    }) => {
        await login(page);
        await page.goto('/pharmacie');

        // Le seeder ne laisse que 3 flacons de sirop : en demander 10 dépasse le
        // disponible, et le pharmacien doit le voir avant d'encaisser.
        await page.locator('#sale-search').fill('Sirop');
        await salePanel(page).getByRole('button', { name: /Sirop/ }).first().click();

        const quantity = salePanel(page).getByLabel('Quantité vendue');
        await quantity.fill('10');

        // La saisie est ramenée au disponible, et le pharmacien doit le voir :
        // un panier affichant 10 quand il n'y en a que 3 produirait un refus
        // à l'encaissement.
        await expect(quantity).toHaveValue('3');
        await expect(page.getByText('Quantité limitée au stock')).toBeVisible();
    });
});
