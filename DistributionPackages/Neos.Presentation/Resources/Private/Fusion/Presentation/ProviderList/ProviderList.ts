import { AlpineComponent } from 'alpinejs'

export type ProviderListFilterComponent = {
    search: string
    country: string
    size: string
    serviceType: string
    view: 'grid' | 'table'
    sortBy: 'name' | null
    sortDir: 'asc' | 'desc' | null
    visibleCount: number

    init: () => void
    filter: () => void
    sort: () => void
}

export default (): AlpineComponent<ProviderListFilterComponent> => ({
    search: '',
    country: '',
    size: '',
    serviceType: '',
    view: 'grid',
    sortBy: null,
    sortDir: null,
    visibleCount: 0,

    init() {
        const grid = this.$refs.grid as HTMLElement
        Array.from(grid.querySelectorAll<HTMLElement>('[data-provider-item]'))
            .sort((a, b) => parseInt(b.dataset.hasBadges ?? '0') - parseInt(a.dataset.hasBadges ?? '0'))
            .forEach(el => grid.appendChild(el))
        this.visibleCount = grid.querySelectorAll('[data-provider-item]').length
    },

    filter() {
        const search = this.search.toLowerCase()
        let count = 0
        ;(this.$refs.grid as HTMLElement)
            .querySelectorAll<HTMLElement>('[data-provider-item]')
            .forEach(el => {
                const matchSearch = !search || (el.dataset.searchText ?? '').includes(search)
                const matchCountry = !this.country || el.dataset.country === this.country
                const matchSize = !this.size || el.dataset.size === this.size
                const matchServiceType = !this.serviceType || (el.dataset.serviceTypes ?? '').split('|').includes(this.serviceType)
                const visible = matchSearch && matchCountry && matchSize && matchServiceType
                el.hidden = !visible
                if (visible) count++
            })
        this.visibleCount = count
    },

    sort() {
        if (this.sortBy === 'name') {
            this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
        } else {
            this.sortBy = 'name'
            this.sortDir = 'asc'
        }
        const grid = this.$refs.grid as HTMLElement
        Array.from(grid.querySelectorAll<HTMLElement>('[data-provider-item]'))
            .sort((a, b) => {
                const aName = a.dataset.name ?? ''
                const bName = b.dataset.name ?? ''
                return this.sortDir === 'asc' ? aName.localeCompare(bName) : bName.localeCompare(aName)
            })
            .forEach(el => grid.appendChild(el))
    },
})
