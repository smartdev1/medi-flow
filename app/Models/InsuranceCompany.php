<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCompany extends Model
{
    use HasFactory;

    /**
     * Le taux de couverture n'est plus porté par la compagnie : il est saisi
     * sur chaque consultation, via `visits.ticket_moderateur`. Une même
     * assurance peut donc couvrir deux patients à des taux différents.
     */
    protected $fillable = ['name'];

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class);
    }
}
