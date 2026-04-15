import { AlpineComponent } from 'alpinejs'

export type CaseListFilterComponent = {
    search: string
    industry: string
    volume: string
    view: 'grid' | 'list'
    sortDir: 'asc' | 'desc'
    visibleCount: number

    init: () => void
    filter: () => void
    sort: () => void
}

export default (): AlpineComponent<CaseListFilterComponent> => ({
    search: '',
    industry: '',
    volume: '',
    view: 'grid',
    sortDir: 'desc',
    visibleCount: 0,

    init() {
        const grid = this.$refs.grid as HTMLElement
        Array.from(grid.querySelectorAll<HTMLElement>('[data-case-item]'))
            .sort((a, b) => parseInt(b.dataset.featured ?? '0') - parseInt(a.dataset.featured ?? '0'))
            .forEach(el => grid.appendChild(el))
        this.visibleCount = grid.querySelectorAll('[data-case-item]').length
    },

    filter() {
        const search = this.search.toLowerCase()
        let count = 0
        ;(this.$refs.grid as HTMLElement)
            .querySelectorAll<HTMLElement>('[data-case-item]')
            .forEach(el => {
                const matchSearch = !search || (el.dataset.searchText ?? '').includes(search)
                const matchIndustry = !this.industry || (!!el.dataset.projectType && el.dataset.projectType === this.industry)
                const matchVolume = !this.volume || (!!el.dataset.projectVolume && el.dataset.projectVolume !== '0' && el.dataset.projectVolume === this.volume)
                const visible = matchSearch && matchIndustry && matchVolume
                el.hidden = !visible
                if (visible) count++
            })
        this.visibleCount = count
    },

    sort() {
        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc'
        const grid = this.$refs.grid as HTMLElement
        Array.from(grid.querySelectorAll<HTMLElement>('[data-launch-date]'))
            .sort((a, b) => {
                const aDate = parseInt(a.dataset.launchDate ?? '0')
                const bDate = parseInt(b.dataset.launchDate ?? '0')
                return this.sortDir === 'asc' ? aDate - bDate : bDate - aDate
            })
            .forEach(el => grid.appendChild(el))
    },
})
