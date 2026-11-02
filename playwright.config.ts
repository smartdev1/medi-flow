import path from 'node:path';
import { defineConfig, devices } from '@playwright/test';

// Chemin absolu : Laravel résout la base SQLite par rapport au dossier du
// projet, et un chemin relatif ne serait pas trouvé depuis le processus qui
// lance la série.
const e2eDatabase = path.join(process.cwd(), 'database', 'e2e.sqlite');

/**
 * Tests de bout en bout.
 *
 * Ils s'exécutent dans un vrai navigateur : c'est la seule façon de vérifier
 * qu'un composant Alpine s'initialise réellement, qu'une recherche renvoie des
 * suggestions et qu'un formulaire se soumet. Les tests PHPUnit ne couvrent que
 * le contrat serveur et n'auraient pas vu passer un composant inerte.
 *
 * L'environnement est isolé : base SQLite dédiée, jamais la base de
 * développement, et les comptes créés sont ceux du seeder `E2ESeeder`.
 */
export default defineConfig({
    testDir: './tests/e2e',
    timeout: 45_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list']],

    use: {
        baseURL: 'http://127.0.0.1:8010',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    webServer: [
        {
            // Base isolée : la base de développement ne doit jamais être
            // touchée par une série de tests qui crée et supprime des données.
            // Cette commande prépare la base puis se termine : elle ne sert
            // aucune URL, contrairement aux deux serveurs ci-dessous.
            // La base SQLite doit exister avant d'être migrée, et `touch` n'est
            // pas disponible partout : la création passe par node, déjà présent.
            command:
                'node -e "const fs=require(\'fs\');fs.closeSync(fs.openSync(\'database/e2e.sqlite\',\'a\'))" ' +
                '&& php artisan migrate:fresh --force ' +
                '&& php artisan db:seed --class=E2ESeeder --force',
            env: {
                APP_ENV: 'local',
                APP_URL: 'http://127.0.0.1:8010',
                DB_CONNECTION: 'sqlite',
                DB_DATABASE: e2eDatabase,
            },
            reuseExistingServer: false,
            timeout: 120_000,
        },
        {
            // Port 8010 et non 8000 : le serveur de développement occupe
            // généralement 8000, et le réutiliser ferait pointer les tests vers
            // la base de développement.
            command: 'php artisan serve --host=127.0.0.1 --port=8010',
            url: 'http://127.0.0.1:8010',
            env: {
                APP_ENV: 'local',
                APP_URL: 'http://127.0.0.1:8010',
                DB_CONNECTION: 'sqlite',
                DB_DATABASE: e2eDatabase,
            },
            reuseExistingServer: false,
            timeout: 60_000,
        },
        {
            // `public/hot` fait pointer les assets vers le serveur Vite : sans
            // lui, la page se charge sans aucun JavaScript et les composants
            // Alpine restent inertes.
            // Vite n'écoute par défaut que sur `::1`, que Playwright ne
            // tente pas : l'interface et le port sont donc imposés.
            // Le port 5273 est distinct de celui du serveur de développement,
            // et `--strictPort` évite qu'il glisse silencieusement sur 5174 ou
            // 5175, ce qui laisserait la page sans JavaScript.
            command: 'npm run dev -- --port 5273 --strictPort --host 127.0.0.1',
            // Pas `url: '/'` : le serveur Vite répond 404 sur la racine, et
            // Playwright considère un 404 comme « pas encore prêt » et expire.
            // Le client Vite renvoie bien un 200 une fois le serveur démarré.
            url: 'http://127.0.0.1:5273/@vite/client',
            reuseExistingServer: false,
            timeout: 60_000,
        },
    ],
});