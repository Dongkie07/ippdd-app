# Frontend Structure Guide for Future OJT Developers

This app uses Laravel + Inertia + Vue 3. The goal of the refactor is simple: page files should wire components together, while logic lives in composables and reusable pieces.

## Important folders

```text
resources/js/
├── Components/              # Shared UI components used by many pages
├── Layouts/                 # App shell / sidebar / page frame
├── Pages/                   # Inertia page entry files
│   ├── Budget.vue           # Thin page wrapper for Department Breakdown
│   ├── Budget/              # Budget page UI components
│   ├── Departments.vue      # Thin page wrapper for Manual Entry
│   ├── Departments/         # Manual Entry components + CRUD composable
│   └── Dashboard/           # Dashboard-only components
├── composables/             # Reusable logic hooks: formatters, charts, sorting, uploads
└── constants/               # Shared constants like fund colors and labels
```

## Debugging guide

### Manual Entry page

Start here:

```text
resources/js/Pages/Departments.vue
```

This file only connects the layout, toolbar, table, forms, and modals.

For bugs in add/edit/delete/new fiscal year behavior, open:

```text
resources/js/Pages/Departments/useDepartments.js
```

For UI-only bugs, open the matching component:

```text
Departments/DepartmentToolbar.vue       # year buttons and add/delete buttons
Departments/DepartmentSummary.vue       # total budget summary
Departments/DepartmentTable.vue         # department rows and action buttons
Departments/DepartmentEditorPanel.vue   # add/edit slide-in form
Departments/NewYearModal.vue            # create fiscal year modal
Departments/ConfirmDeleteModal.vue      # delete confirmation modal
Departments/FeedbackToast.vue           # success/error messages
Departments/EmptyState.vue              # empty year / empty department state
```

### Department Breakdown page

Start here:

```text
resources/js/Pages/Budget.vue
```

For bugs in table data, fund mix, year comparison, or chart data, open:

```text
resources/js/composables/budget/useBudgetBreakdown.js
```

For UI-only bugs, open:

```text
Budget/BudgetTabs.vue
Budget/RankingTab.vue
Budget/FundMixTab.vue
Budget/YearComparisonTab.vue
Budget/YearSummaryCards.vue
```

## Rule for future changes

Do not put everything back into one giant Vue file. Humanity has suffered enough.

Use this pattern:

```text
Page.vue                  # imports + layout + component wiring only
composables/page/usePageName.js  # state, computed data, submit/delete actions
Page/SmallComponent.vue   # one visible section of the UI
```

## Shared WFP constants

Fund labels and colors now live here:

```text
resources/js/constants/wfp.js
```

Use that file instead of hard-coding fund colors in every component.

## Build check

After changing Vue files, run:

```bash
npm install
npm run build
```

This refactor was checked with `npm run build` successfully.
