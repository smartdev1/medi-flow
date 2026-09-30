<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * La navigation est le premier écran du logiciel : un lien manquant ou un lien
 * qui mène à un 403 fait perdre du temps au personnel à chaque poste.
 *
 * Ces tests rendent le menu pour chaque rôle et vérifient deux choses : les
 * entrées attendues sont présentes, et aucun lien affiché ne pointe vers une
 * route que le rôle ne peut pas atteindre.
 */
class NavigationMenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rôles et écrans que chaque rôle doit trouver dans le menu.
     *
     * @return array<string, array{0: \App\Enums\Role, 1: array<int, string>}>
     */
    public static function expectedLinks(): array
    {
        return [
            'docteur general' => [Role::DocteurGeneral, [
                'docteurgeneral.index', 'accueil.index', 'attente.index', 'medecin.index',
                'laboratoire.index', 'pharmacie.index', 'logistique.index',
                'hospitalisation.index', 'admission-requests.index', 'observation.index', 'chambres.index',
                'caisse.index', 'sortie.index', 'insurance.index', 'patients.index',
                'users.index', 'acts.index',
            ]],
            'medecin' => [Role::Medecin, [
                'medecin.index', 'hospitalisation.index', 'admission-requests.index',
                'observation.index', 'chambres.index',
            ]],
            'caissier' => [Role::Caissier, [
                'caisse.index', 'caisse.sessions.index',
            ]],
            'accueil' => [Role::Accueil, [
                'accueil.index', 'attente.index', 'sortie.index', 'patients.index',
            ]],
            'aide soignante' => [Role::AideSoignante, [
                'accueil.index', 'attente.index', 'admission-requests.index', 'patients.index',
            ]],
            'pharmacien' => [Role::Pharmacien, ['pharmacie.index']],
            'laborantin' => [Role::Laborantin, ['laboratoire.index']],
            'logisticien' => [Role::Logistique, ['logistique.index']],
        ];
    }

    public function test_each_role_finds_its_screens_in_the_menu(): void
    {
        foreach (self::expectedLinks() as $label => [$role, $routes]) {
            $user = User::factory()->withRole($role)->create();

            $content = $this->actingAs($user)
                ->get(route($role->homeRouteName()))
                ->assertOk()
                ->getContent();

            foreach ($routes as $name) {
                $this->assertStringContainsString(
                    route($name),
                    $content,
                    "Le menu de « {$label} » ne propose pas « {$name} »."
                );
            }
        }
    }

    public function test_no_role_is_offered_a_link_it_cannot_open(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->withRole($role)->create();

            $content = $this->actingAs($user)
                ->get(route($role->homeRouteName()))
                ->assertOk()
                ->getContent();

            $links = array_unique(array_filter(
                preg_match_all('/<a[^>]+href="([^"]+)"/', $content, $m) ? $m[1] : []
            ));

            $this->assertNotEmpty($links, "Aucun lien rendu pour « {$role->label()} ».");

            foreach ($links as $href) {
                $routeName = $this->routeNameFor($href);

                if ($routeName === null) {
                    continue;
                }

                $this->assertTrue(
                    $this->roleCanReach($role, $routeName),
                    "Le menu de « {$role->label()} » propose « {$routeName} » ("
                        .Route::getRoutes()->getByName($routeName)?->uri()
                        .') alors que le rôle ne peut pas y accéder.'
                );
            }
        }
    }

    /**
     * Un parent dépliable ne doit jamais s'ouvrir sur du vide : s'il est
     * affiché, au moins un de ses enfants l'est aussi.
     */
    public function test_a_collapsible_section_is_never_rendered_empty(): void
    {
        $sections = [
            'Hospitalisation' => ['hospitalisation.index', 'observation.index', 'chambres.index', 'admission-requests.index'],
            'Gestion' => ['insurance.index', 'patients.index', 'users.index', 'acts.index'],
        ];

        foreach (Role::cases() as $role) {
            $user = User::factory()->withRole($role)->create();

            $content = $this->actingAs($user)
                ->get(route($role->homeRouteName()))
                ->assertOk()
                ->getContent();

            foreach ($sections as $label => $childRoutes) {
                if (! str_contains($content, '>'.$label.'<')) {
                    continue;
                }

                $rendered = array_filter(
                    $childRoutes,
                    fn (string $name) => str_contains($content, route($name))
                );

                $this->assertNotEmpty(
                    $rendered,
                    "La section « {$label} » est proposée à « {$role->label()} » mais aucun de ses liens n'est rendu."
                );
            }
        }
    }

    private function routeNameFor(string $href): ?string
    {
        $path = parse_url($href, PHP_URL_PATH) ?: '/';

        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === ltrim($path, '/')) {
                return $route->getName();
            }
        }

        return null;
    }

    /**
     * Un rôle peut atteindre une route si le middleware `role:` qui la protège
     * le liste, ou s'il s'agit d'une route sans restriction de rôle.
     *
     * L'administrateur fait exception : EnsureUserHasRole l'autorise partout,
     * pour lui donner la visibilité de supervision sur tous les modules.
     */
    private function roleCanReach(Role $role, string $routeName): bool
    {
        $route = Route::getRoutes()->getByName($routeName);

        if ($route === null) {
            return true;
        }

        if ($role === Role::Admin) {
            return true;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'role:')) {
                continue;
            }

            return in_array($role->value, explode(',', substr($middleware, 5)), true);
        }

        return true;
    }
}
