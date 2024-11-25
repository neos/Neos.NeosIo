import BaseComponent from "./BaseComponent";

/**
 * Adapted code from https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Roles/tab_role#example
 */
export default class Tabs extends BaseComponent {
    constructor(el) {
        super(el);

        const tabwrapper = this.el;

        const tabList = tabwrapper.querySelector('[role="tablist"]');
        const tabs = tabwrapper.querySelectorAll('[role="tab"]');
        const tabPanels = tabwrapper.querySelectorAll('[role="tabpanel"]');
        tabPanels.forEach((panel, index) => index !== 0 && panel.toggleAttribute("hidden", true));

        // Add a click event handler to each tab
        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                tabs.forEach((t) => t.setAttribute("aria-selected", t !== tab ? 'false' : 'true'));

                const controls = tab.getAttribute("aria-controls");
                tabPanels.forEach((panel) => panel.toggleAttribute("hidden", panel.id !== controls));
            });
        });

        // Enable arrow navigation between tabs in the tab list

        let tabFocus = 0;
        tabList.addEventListener("keydown", (e) => {
            // Move right
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                tabs[tabFocus].setAttribute("tabindex", -1);
                if (e.key === 'ArrowRight') {
                    tabFocus++;
                    // If we're at the end, go to the start
                    if (tabFocus >= tabs.length) {
                        tabFocus = 0;
                    }
                    // Move left
                } else if (e.key === 'ArrowLeft') {
                    tabFocus--;
                    // If we're at the start, move to the end
                    if (tabFocus < 0) {
                        tabFocus = tabs.length - 1;
                    }
                }

                tabs[tabFocus].setAttribute("tabindex", 0);
                tabs[tabFocus].focus();
            }
        });
    }
}
