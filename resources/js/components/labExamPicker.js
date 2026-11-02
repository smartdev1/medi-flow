/**
 * Composant Alpine de prescription d'examens de laboratoire.
 *
 * Le médecin compose une liste d'examens avant d'envoyer : chaque examen est
 * choisi dans le catalogue `lab_exams`, et son prix est résolu côté serveur à
 * partir de ce catalogue. Aucun prix n'est saisi ni affiché ici : le médecin
 * choisit une analyse, il ne la chiffre pas, et un tarifodoit donc venir d'une
 * source unique.
 *
 * Un examen absent du catalogue n'est pas saisissable : sans tarif de
 * référence, il serait consommé au laboratoire sans jamais être facturé.
 *
 * La logique vit dans un fichier compilé par Vite plutôt que dans un attribut
 * `x-data` écrit en ligne dans une vue Blade, où une erreur de syntaxe
 * produirait un composant silencieusement inerte.
 */
export default function labExamPicker(config = {}) {
    return {
        searchUrl: config.searchUrl ?? '',
        // Examens déjà prescrits lors d'une soumission refusée, afin de ne pas
        // obliger le médecin à tout resélectionner.
        items: config.items ?? [],
        query: '',
        results: [],
        open: false,
        loading: false,
        timer: null,
        // Ordre d'émission de la dernière requête : une réponse plus lente qu'une
        // plus récente ne doit jamais écraser les résultats courants.
        latest: 0,

        get hasResults() {
            return this.open && this.results.length > 0;
        },

        get hasNoMatch() {
            return (
                this.open &&
                this.query.trim() !== '' &&
                this.results.length === 0 &&
                !this.loading
            );
        },

        get canSubmit() {
            return this.items.length > 0;
        },

        alreadyAdded(labExamId) {
            return this.items.some((item) => item.lab_exam_id === labExamId);
        },

        async search() {
            const term = this.query.trim();

            if (term.length === 0) {
                this.results = [];
                return;
            }

            const ticket = ++this.latest;
            this.loading = true;

            let response;

            try {
                response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(term)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
            } catch (error) {
                console.warn('Recherche du catalogue impossible:', error);

                if (ticket === this.latest) {
                    this.results = [];
                    this.loading = false;
                }

                return;
            }

            if (!response.ok) {
                console.warn(
                    `Recherche du catalogue refusée (${response.status}) pour « ${term} »`
                );
            }

            let data = [];

            try {
                data = await response.json();
            } catch (error) {
                console.warn('Réponse du catalogue illisible:', error);
            }

            if (!Array.isArray(data)) {
                data = [];
            }

            if (ticket === this.latest) {
                this.results = data;
                this.open = true;
                this.loading = false;
            }
        },

        onInput() {
            this.open = true;
            this.results = [];

            clearTimeout(this.timer);

            if (this.query.trim().length === 0) {
                this.results = [];
                this.open = false;
                this.loading = false;
                return;
            }

            this.timer = setTimeout(() => this.search(), 250);
        },

        add(exam) {
            // Le même examen demandé deux fois sur une même visite est presque
            // toujours une double frappe, pas une seconde demande.
            if (this.alreadyAdded(exam.id)) {
                this.query = '';
                this.results = [];
                this.open = false;
                return;
            }

            this.items.push({
                lab_exam_id: exam.id,
                name: exam.name,
                category: exam.category,
            });

            this.query = '';
            this.results = [];
            this.open = false;
        },

        remove(index) {
            this.items.splice(index, 1);
        },
    };
}