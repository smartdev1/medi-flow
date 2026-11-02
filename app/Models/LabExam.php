<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabExam extends Model
{
    use HasFactory;

    /**
     * Accents français, pour les deux casses.
     *
     * Le médecin tape sans accent (« hep ») alors que le libellé du catalogue
     * en porte un (« hépatique »). La collation MySQL s'en accommodait, mais
     * SQLite — qui sert aux tests — compare octet à octet : sans ce
     * repli, la recherche se comporte différemment selon l'environnement.
     */
    private const ACCENTS = [
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'ç' => 'c', 'Ç' => 'C',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ñ' => 'n', 'Ñ' => 'N',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ý' => 'y', 'ÿ' => 'y',
        'Ý' => 'Y', 'Ÿ' => 'Y',
        'œ' => 'oe', 'Œ' => 'OE',
    ];

    protected $fillable = [
        'name',
        'category',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    /**
     * Maintient `search_index` alignée sur le libellé et la catégorie.
     */
    protected static function booted(): void
    {
        static::saving(function (self $exam) {
            $exam->search_index = self::buildSearchIndex($exam->name, $exam->category);
        });
    }

    /**
     * Construit l'index de recherche : minuscules, sans accents, libellé et
     * catégorie réunis pour qu'une seule colonne serve aux deux.
     */
    public static function buildSearchIndex(?string $name, ?string $category): string
    {
        return trim(self::unaccent(trim((string) $name).' '.trim((string) $category)));
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Recherche un examen sur son libellé ou sa catégorie, sans distinguer
     * casse ni accents, et sans rien renvoyer sur une saisie vide.
     */
    public function scopeSearching($query, string $term)
    {
        $term = trim($term);

        if ($term === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('search_index', 'like', '%'.self::unaccent($term).'%');
    }

    /**
     * Retire les accents d'un terme saisi au clavier.
     *
     * Le médecin tape sans accent (« hep ») alors que le libellé du catalogue
     * en porte un (« hépatique »).
     */
    public static function unaccent(string $value): string
    {
        return strtr(mb_strtolower($value), self::ACCENTS);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0, ',', ' ').' FCFA';
    }
}
