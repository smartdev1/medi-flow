import './bootstrap';

import Alpine from 'alpinejs';

import labExamPicker from './components/labExamPicker.js';
import pharmacySale from './components/pharmacySale.js';

// Les données Alpine doivent être enregistrées avant `start()`, faute de quoi
// le premier rendu de la page ne les trouve pas.
Alpine.data('labExamPicker', labExamPicker);
Alpine.data('pharmacySale', pharmacySale);

window.Alpine = Alpine;

Alpine.start();