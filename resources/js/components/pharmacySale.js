/**
 * Composant Alpine du point de vente pharmacie.
 *
 * Le pharmacien compose un panier avant d'encaisser. Chaque médicament est
 * choisi dans le catalogue `medications` ; le prix affiché ici sert à annoncer
 * le montant à recouvrer, mais il n'est jamais transmis : le serveur relit
 * `unit_price` dans le catalogue. Un tarif falsifié côté navigateur ne ferait
 * que déporter l'écart entre le total annoncé et le total facturé, et c'est
 * la facturation qui fait foi.
 *
 * La logique vit dans un fichier compilé par Vite plutôt que dans un attribut
 * `x-data` écrit en ligne dans une vue Blade, où une erreur de syntaxe
 * produirait un composant silencieusement inerte.
 */
export default function pharmacySale(config = {}) {
    return {
        searchUrl: config.searchUrl ?? '',
        patientSearchUrl: config.patientSearchUrl ?? '',
        methodOptions: config.methodOptions ?? [],
        // Panier conservé lors d'une soumission refusée, afin de ne pas obliger
        // le pharmacien à tout recomposer.
        items: config.items ?? [],
        method: config.method ?? '',
        patient: config.patient ?? null,
        query: '',
        results: [],
        patientQuery: '',
        patientResults: [],
        open: false,
        patientOpen: false,
        loading: false,
        timer: null,
        // Le minuteur du patient est distinct de celui des médicaments : les
        // deux recherches peuvent être saisies en même temps, et un minuteur
        // partagé ferait annuler la frappe la plus récente.
        patientTimer: null,
        // Ordre d'émission de la dernière requête : une réponse plus lente qu'une
        // plus récente ne doit jamais écraser les résultats courants.
        latest: 0,
        latestPatient: 0,

        get total() {
            return this.items.reduce(
                (sum, item) => sum + item.quantity * item.unit_price,
                0
            );
        },

        get hasResults() {
            return this.open && this.results.length > 0;
        },

        get canSubmit() {
            return this.items.length > 0 && this.patient !== null && this.method !== '';
        },

        quantityOf(medicationId) {
            const line = this.items.find(
                (item) => item.medication_id === medicationId
            );

            return line ? line.quantity : 0;
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
                response = await fetch(
                    `${this.searchUrl}?q=${encodeURIComponent(term)}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );
            } catch (error) {
                console.warn('Recherche du catalogue impossible:', error);

                if (ticket === this.latest) {
                    this.results = [];
                    this.loading = false;
                }

                return;
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

        async searchPatients() {
            const term = this.patientQuery.trim();

            if (term.length === 0) {
                this.patientResults = [];
                this.patientOpen = false;
                return;
            }

            const ticket = ++this.latestPatient;

            let data = [];

            try {
                const response = await fetch(
                    `${this.patientSearchUrl}?q=${encodeURIComponent(term)}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                data = await response.json();
            } catch (error) {
                console.warn('Recherche du patient impossible:', error);
            }

            if (!Array.isArray(data)) {
                data = [];
            }

            if (ticket === this.latestPatient) {
                this.patientResults = data;
                this.patientOpen = true;
            }
        },

        onPatientInput() {
            this.patient = null;
            this.patientOpen = true;
            clearTimeout(this.patientTimer);
            this.patientTimer = setTimeout(() => this.searchPatients(), 250);
        },

        selectPatient(patient) {
            this.patient = patient;
            this.patientQuery = `${patient.first_name} ${patient.last_name}`;
            this.patientResults = [];
            this.patientOpen = false;
        },

        add(medication) {
            const line = this.items.find(
                (item) => item.medication_id === medication.id
            );

            if (line) {
                line.quantity += 1;

                if (line.quantity > medication.stock_quantity) {
                    line.quantity = medication.stock_quantity;
                }

                return;
            }

            // Un médicament en rupture n'est pas proposé : l'ajouter au panier
            // ne pourrait aboutir qu'à un refus au moment d'encaisser.
            if (!medication.stock_quantity || medication.stock_quantity < 1) {
                return;
            }

            this.items.push({
                medication_id: medication.id,
                name: medication.name,
                unit: medication.unit,
                unit_price: medication.unit_price,
                stock_quantity: medication.stock_quantity,
                quantity: 1,
            });
        },

        /**
         * Aligne le champ de saisie sur la quantité retenue.
         *
         * Borner la valeur dans l'état ne suffit pas : le champ garde ce que le
         * pharmacien a tapé, et le panier comme le total afficheraient une
         * quantité que la saisie ne montre pas. Sans cette remise à niveau, la
         * ligne paraît incohérente avec elle-même.
         */
        setQuantity(medicationId, value) {
            const line = this.items.find(
                (item) => item.medication_id === medicationId
            );

            if (!line) {
                return;
            }

            const requested = Number.parseInt(value, 10);

            if (Number.isNaN(requested) || requested < 1) {
                line.quantity = 1;

                return;
            }

            // Le stock disponible borne la quantité : au-delà, la vente serait
            // refusée au moment de l'encaissement, après avoir fait perdre le
            // temps du pharmacien. La saisie est ramenée au disponible.
            line.quantity = Math.min(requested, line.stock_quantity);
        },

        /** Remplace la valeur affichée d'une ligne après sa correction. */
        syncQuantityField(medicationId) {
            this.$nextTick(() => {
                const selector = `[data-medication="${medicationId}"]`;
                const field = this.$root.querySelector(selector);

                if (!field) {
                    return;
                }

                const line = this.items.find(
                    (item) => item.medication_id === medicationId
                );

                if (line) {
                    field.value = String(line.quantity);
                }
            });
        },

        remove(index) {
            this.items.splice(index, 1);
        },
    };
}
