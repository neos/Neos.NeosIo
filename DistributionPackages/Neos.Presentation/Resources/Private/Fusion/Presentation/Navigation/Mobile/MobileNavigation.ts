import { AlpineComponent } from 'alpinejs'

export type MobileMenuComponent = {
    mobileMenuIsOpen: boolean
    expandedIds: { [key: string]: boolean }

    toggleMobileMenu: () => void
    closeMobileMenu: () => void
    toggleSubMenu: (submenuId: string) => void
    isExpanded: (submenuId: string) => boolean
}

export default (): AlpineComponent<MobileMenuComponent> => ({
    mobileMenuIsOpen: false,
    expandedIds: {},

    init: function () {
        console.log('Initializing mobile menu component')
        const menu = this.$refs.mobileMenu as HTMLDivElement
        // get all links with class ".state-active" in the menu
        const activeMenuItems = menu.querySelectorAll('a.state-active') as NodeListOf<HTMLLinkElement>
        // set the expandedIds for all active menu items to true,
        // so that the submenus of the active menu items are expanded when the mobile menu is opened
        activeMenuItems.forEach((item: HTMLLinkElement) => {
            if (item.dataset.id) {
                this.expandedIds[item.dataset.id] = true
            }
        })
    },

    toggleMobileMenu(): void {
        this.mobileMenuIsOpen = !this.mobileMenuIsOpen
    },

    closeMobileMenu(): void {
        this.mobileMenuIsOpen = false
        this.expandedIds = {}
    },

    toggleSubMenu(submenuId: string): void {
        if (!this.expandedIds[submenuId]) {
            // no interaction yet trough toggle, so the id is not in expandedIds
            // therefore we set it to true (expanded)
            this.expandedIds[submenuId] = true
        } else {
            // toggle the current state
            this.expandedIds[submenuId] = !this.expandedIds[submenuId]
        }
    },

    isExpanded(submenuId: string): boolean {
        if (!this.mobileMenuIsOpen) {
            return false
        }

        if (!this.expandedIds[submenuId]) {
            // no interaction yet trough toggle, so the id is not in expandedIds
            // therefore it is not expanded
            return false
        } else {
            // return the current state
            return this.expandedIds[submenuId]
        }
    },
})
