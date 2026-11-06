<?php

namespace App\Traits;

use App\Models\Visit;
use Symfony\Component\HttpFoundation\Response;

trait GuardsClosedVisit
{
    protected function abortIfVisitClosed(?Visit $visit): void
    {
        if ($visit && $visit->isClosed()) {
            abort(Response::HTTP_FORBIDDEN, "Ce dossier est clôturé et ne peut plus être modifié.");
        }
    }
}
