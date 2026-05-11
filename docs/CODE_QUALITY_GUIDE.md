# Code Quality Guide

This project now uses a cleaner Laravel/Vue structure so the system is easier to defend, maintain, and extend.

## Naming and documentation

- Use intention-revealing names such as `officeIdentity`, `effectiveFromYear`, and `currentName`.
- Avoid magic numbers by placing repeated values in constants, for example `MIN_YEAR`, `MAX_YEAR`, and `INSERT_CHUNK_SIZE`.
- Prefer self-documenting code. Comments should explain why a decision exists, not repeat what the code already says.

## Functional structure

- Controllers should stay thin and delegate business logic to services.
- Office rename and history logic belongs in `app/Services/Offices`.
- WFP import parsing and saving stays inside `app/Services/Wfp`.
- Vue pages should wire components together. Page behavior belongs in composables like `useDepartments` and `useOffices`.

## Structural techniques

- DRY: shared office-name logic is centralized in `OfficeIdentityService` and `OfficeRegistryService`.
- KISS: charts group by `office_id` / `office_key`, while labels are chosen separately for current or historical display.
- Error handling should stay near the action layer, while business rules stay in services.

## Maintenance

- Run PHP formatting checks with Laravel Pint:

```bash
./vendor/bin/pint --test
```

- Auto-format PHP files with:

```bash
./vendor/bin/pint
```

- Rebuild frontend assets after editing Vue files:

```bash
npm install
npm run build
```

- For MySQL setups, run migrations after pulling this update:

```bash
php artisan migrate
```
