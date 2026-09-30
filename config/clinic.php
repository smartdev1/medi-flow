<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ticket modérateur
    |--------------------------------------------------------------------------
    |
    | Taux par défaut, en pourcentage du coût des actes, appliqué à la part
    | restant à charge de l'assuré. L'hospitalisation et la mise en
    | observation en sont exclues : leur montant est arrêté par l'accueil ou,
    | à défaut, suit le prix de la chambre.
    |
    */

    'default_ticket_rate' => 20,

    /*
    |--------------------------------------------------------------------------
    | Seuil de sortie
    |--------------------------------------------------------------------------
    |
    | Solde restant en FCFA en dessous duquel la sortie du patient est
    | autorisée malgré une facture non soldée. Zéro : aucun reste dû n'est
    | toléré, la sortie patient est bloquée jusqu'à encaissement effectif par
    | la caisse. La clôture de la consultation par le médecin reste libre.
    |
    */

    'discharge_unpaid_threshold' => 0,

    /*
    |--------------------------------------------------------------------------
    | Moyens de paiement
    |--------------------------------------------------------------------------
    */

    'payment_methods' => [
        'Espèces' => 'Espèces',
        'Carte bancaire' => 'Carte bancaire',
        'Mobile Money' => 'Mobile Money',
        'Assurance CNAM' => 'Assurance CNAM',
        'CMU' => 'CMU',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuts d'encaissement
    |--------------------------------------------------------------------------
    */

    'payment_statuses' => [
        \App\Models\Payment::STATUS_PAID => 'Payé',
        \App\Models\Payment::STATUS_PARTIAL => 'Partiellement payé',
        \App\Models\Payment::STATUS_PENDING => 'En attente',
    ],
];
