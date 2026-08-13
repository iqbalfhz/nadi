// Makes the Filament sidebar's navigation groups behave like an accordion:
// opening one group collapses every other top-level group. Implemented by
// wrapping Alpine's built-in 'sidebar' store instead of editing vendor
// Blade/JS, so it survives `composer update`.
document.addEventListener('alpine:initialized', () => {
    const sidebar = window.Alpine.store('sidebar')

    if (!sidebar || sidebar.__nadiAccordionPatched) {
        return
    }

    sidebar.__nadiAccordionPatched = true

    const mainGroupLabels = () =>
        Array.from(
            document.querySelectorAll(
                '.fi-main-sidebar > .fi-sidebar-nav > .fi-sidebar-nav-groups > [data-group-label]',
            ),
        ).map((el) => el.dataset.groupLabel)

    const collapseAllExcept = (openLabel) => {
        if (!Array.isArray(sidebar.collapsedGroups)) {
            sidebar.collapsedGroups = []
        }

        mainGroupLabels().forEach((label) => {
            if (label !== openLabel && !sidebar.collapsedGroups.includes(label)) {
                sidebar.collapsedGroups = sidebar.collapsedGroups.concat(label)
            }
        })
    }

    const originalToggle = sidebar.toggleCollapsedGroup.bind(sidebar)

    sidebar.toggleCollapsedGroup = function (group) {
        const wasCollapsed = Array.isArray(this.collapsedGroups) && this.collapsedGroups.includes(group)

        originalToggle(group)

        if (wasCollapsed) {
            collapseAllExcept(group)
        }
    }

    // Also normalize on first load: keep whichever group holds the current
    // page open (if any), otherwise leave the first already-open group open.
    const activeGroup = document.querySelector(
        '.fi-main-sidebar > .fi-sidebar-nav > .fi-sidebar-nav-groups > .fi-sidebar-group.fi-active',
    )
    const openLabel =
        activeGroup?.dataset.groupLabel ??
        mainGroupLabels().find(
            (label) => !(Array.isArray(sidebar.collapsedGroups) && sidebar.collapsedGroups.includes(label)),
        )

    if (openLabel) {
        collapseAllExcept(openLabel)
    }
})
