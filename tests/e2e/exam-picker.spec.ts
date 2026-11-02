import { expect, test, type Page } from '@playwright/test';

import { resetDatabase } from './support/database';

/**
 * Tests de bout en bout du sélecteur d'examens du médecin.
 *
 * Ce composant avait été annoncé fonctionnel alors que la liste restait
 * invisible : les tests PHPUnit ne vérifient que le contrat serveur, et une
 * expression Alpine invalide ne les fait pas échouer. Ces tests exécutent la
 * page dans un vrai navigateur.
 */

const DOCTOR = { email: 'adama.traore@cmsm.ci', password: 'password' };

async function login(page: Page) {
    await page.goto('/login');
    await page.fill('input[name="email"]', DOCTOR.email);
    await page.fill('input[name="password"]', DOCTOR.password);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/(medecin|accueil|caisse)/);
}

/**
 * Ouvre la fiche du patient, puis le tiroir « Labo ».
 *
 * Le sélecteur n'existe qu'à l'intérieur de la fiche : sans patient, le tableau
 * de bord affiche un simple appel à les sélectionner. Confirmer la prise en
 * consultation recharge `/medecin?patient=…` — le chemin ne change pas, seul le
 * paramètre, et le clic suivant doit attendre la fin de ce rechargement.
 */
async function openLabDrawer(page: Page) {
    await page.goto('/medecin');

    await page.getByRole('button', { name: /Kouassi/ }).click();

    await Promise.all([
        page.waitForURL(/[?&]patient=\d+/),
        page.getByRole('button', { name: 'Confirmer' }).click(),
    ]);

    await page.getByRole('button', { name: 'Labo' }).click();

    await expect(page.locator('#exam-search')).toBeVisible();
}

/**
 * Suggestions du catalogue, limitées au composant.
 *
 * Les noms d'examens apparaissent aussi dans le sélecteur d'actes de la
 * consultation : sans ce scope, le clic porterait sur l'<option> de ce select
 * plutôt que sur la suggestion du picker.
 */
function suggestions(page: Page) {
    return page.locator('[x-data^="labExamPicker("] button[type="button"]');
}

test.describe("Sélecteur d'examens du médecin", () => {
    test.beforeEach(() => {
        resetDatabase();
    });

    test('le composant Alpine est bien initialisé', async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await login(page);
        await openLabDrawer(page);

        const picker = page.locator('[x-data^="labExamPicker("]');
        await expect(picker).toHaveCount(1);

        expect(errors).toEqual([]);
    });

    test('la saisie propose des examens du catalogue', async ({ page }) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));

        await login(page);
        await openLabDrawer(page);

        const search = page.locator('#exam-search');
        await search.fill('glyc');

        // C'est l'assertion qui manquait : la liste doit réellement apparaître.
        await expect(suggestions(page).filter({ hasText: 'Glycémie (à jeun / post-prandiale)' })).toHaveCount(1);

        expect(errors).toEqual([]);
    });

    test('la recherche ignore les accents', async ({ page }) => {
        await login(page);
        await openLabDrawer(page);

        // « glycemie » sans accent doit retrouver « Glycémie ».
        await page.locator('#exam-search').fill('glycemie');
        await expect(suggestions(page).filter({ hasText: 'Glycémie (à jeun / post-prandiale)' })).toHaveCount(1);
    });

    test('plusieurs examens peuvent être sélectionnés puis envoyés', async ({ page }) => {
        await login(page);
        await openLabDrawer(page);

        const search = page.locator('#exam-search');

        await search.fill('glyc');
        await suggestions(page).filter({ hasText: 'Glycémie (à jeun / post-prandiale)' }).click();
        await expect(page.getByText('1 examen(s) à demander')).toBeVisible();

        await search.fill('lipid');
        await suggestions(page).filter({ hasText: 'Bilan lipidique' }).click();
        await expect(page.getByText('2 examen(s) à demander')).toBeVisible();

        await page.getByRole('button', { name: 'Envoyer au laboratoire' }).click();

        await expect(page.getByText('2 examens demandés')).toBeVisible();
    });

    test('le prix n\'est jamais affiché au médecin', async ({ page }) => {
        await login(page);
        await openLabDrawer(page);

        await page.locator('#exam-search').fill('glyc');
        await expect(suggestions(page).filter({ hasText: 'Glycémie' })).toHaveCount(1);

        // Le tarif est résolu côté serveur, depuis le catalogue. Il ne doit
        // apparaître ni dans les suggestions, ni après sélection.
        // La recherche porte sur le composant seul : les autres tiroirs de la
        // fiche, comme l'ordonnance, affichent légitimement des prix.
        const picker = page.locator('[x-data^="labExamPicker("]');
        await expect(picker.getByText(/\d[\d\s.]*\s*FCFA/)).toHaveCount(0);
    });
});