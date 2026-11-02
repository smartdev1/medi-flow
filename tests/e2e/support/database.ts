import { execFileSync } from 'node:child_process';
import path from 'node:path';

/**
 * Remet la base de test dans son état initial.
 *
 * Les tests d'un même exécutant partagent le serveur et la base : prendre un
 * patient en consultation le fait sortir de la file d'attente, si bien que le
 * test suivant ne trouve plus personne à sélectionner. Réinitialiser avant
 * chaque test rend chacun indépendant de l'ordre d'exécution.
 *
 * La commande vide les tables au lieu de rejouer les migrations : elle prend
 * bien moins de temps, et surtout elle n'ouvre pas de transaction concurrente
 * sur la base pendant que le serveur y accède, ce qui faisait dépasser le délai
 * des tests sans lever aucune erreur visible.
 */
export function resetDatabase(): void {
    const root = process.cwd();

    execFileSync('php', ['artisan', 'e2e:reset'], {
        cwd: root,
        env: {
            ...process.env,
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: path.join(root, 'database', 'e2e.sqlite'),
        },
        stdio: 'ignore',
    });
}