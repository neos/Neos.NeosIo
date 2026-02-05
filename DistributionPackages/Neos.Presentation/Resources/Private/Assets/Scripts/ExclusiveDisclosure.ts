import { Alpine as AlpineType } from 'alpinejs';

export default function (Alpine: AlpineType) {
    Alpine.data('exclusiveDisclosure', function (id: string | number) {
        return {
            id,
            active: null as string | number | null,
            get expanded(): boolean {
                return this.active === this.id;
            },
            set expanded(id: string | number) {
                this.active = id ? this.id : null;
            },
            init() {
                Alpine.bind(this.$root, this.root);
            },
            root: {
                ['x-model']: 'expanded',
            },
        };
    });
}
