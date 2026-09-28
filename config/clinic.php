<?php

return [
    'default_ticket_rate' => 20,
    'discharge_unpaid_threshold' => 0,

    'default_vitals' => [
        'Médecin généraliste' => ['Tension', 'Température', 'Pouls', 'Saturation'],
        'Urgence' => ['Tension', 'Température', 'Pouls', 'Saturation'],
        'Pédiatrie' => ['Température', 'Pouls', 'Saturation', 'Poids'],
    ],

    'payment_methods' => [
        'Espèces' => 'Espèces',
        'Carte bancaire' => 'Carte bancaire',
        'Mobile Money' => 'Mobile Money',
        'Assurance CNAM' => 'Assurance CNAM',
        'CMU' => 'CMU',
    ],

    'payment_statuses' => [
        'Payé' => 'Payé',
        'Partiellement payé' => 'Partiellement payé',
        'En attente' => 'En attente',
    ],
];
