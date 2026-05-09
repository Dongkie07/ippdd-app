# IPPDD Executive Dashboard

Institutional Planning and Project Development Division (IPPDD) dashboard for uploading WFP spreadsheets, tracking annual budget allocations, viewing office/department breakdowns, and exporting budget data.

This system uses:

- **Laravel 12** for the backend
- **Vue 3 + Inertia.js** for the frontend
- **Vite + Tailwind CSS** for assets and styling
- **PhpSpreadsheet** for reading uploaded WFP Excel files
- **Chart.js** for dashboard and budget charts

## Main Features

- Executive dashboard with budget KPIs, top offices, fund distribution, and yearly trends
- WFP Excel upload and parsing
- Manual department/year entry
- Department breakdown by ranking, fund mix, and year comparison
- Excel and CSV export
- Dynamic fiscal years, so the system is not hardcoded to only 2024–2026

## Project Structure Notes

Important folders:

```text
app/Http/Controllers/
├── DashboardController.php       # Dashboard data
├── BudgetController.php          # Department Breakdown data
├── WfpImportController.php       # Upload page, parse request, confirm request
├── DepartmentController.php      # Manual entry CRUD
└── ExportController.php          # Excel / CSV export

app/Services/Wfp/
├── WfpSpreadsheetParser.php              # Coordinates spreadsheet parsing
├── WfpSheetLocator.php                   # Finds STATUS/WFP sheets
├── WfpBudgetExtractor.php                # Reads budget rows
├── WfpPerformanceIndicatorExtractor.php  # Reads PI rows when available
├── WfpImportPersister.php                # Saves confirmed upload rows
├── WfpAmountParser.php                   # Cleans peso values
└── WfpReadFilter.php                     # Limits spreadsheet rows/columns read

resources/js/Pages/
├── Dashboard.vue                 # Executive Dashboard page shell
├── Budget.vue                    # Department Breakdown page shell
├── Departments.vue               # Manual entry page shell
└── Upload.vue                    # WFP upload page shell

resources/js/Pages/Budget/
├── BudgetTabs.vue
├── RankingTab.vue
├── FundMixTab.vue
├── YearComparisonTab.vue
└── YearSummaryCards.vue

resources/js/composables/
├── budget/useBudgetBreakdown.js  # Department Breakdown data logic
├── useChartConfigs.js            # Chart.js data/options builders
├── useExpandRows.js
├── useFormatters.js
├── useTableSort.js
└── useUploadLogic.js
```

Keep page files thin. Put reusable logic in `resources/js/composables/`, UI sections in sub-components, and backend business logic in `app/Services/`. Future OJT student, this is your map. Treasure it. The alternative is console logging through a 400-line component like a cave explorer with a dying flashlight.

## Requirements

Install these before running the system:

- PHP **8.2 or higher**
- Composer
- Node.js **20 or higher** recommended
- npm
- MySQL or MariaDB
- PHP extensions commonly needed by Laravel and PhpSpreadsheet:
  - `pdo_mysql`
  - `mbstring`
  - `xml`
  - `dom`
  - `xmlreader`
  - `xmlwriter`
  - `zip`
  - `fileinfo`
  - `openssl`

For Windows, using **Laragon** is usually easier than manually fighting PHP extensions in XAMPP, because apparently software installation also needed a villain arc.

## Fresh Installation

### 1. Clone or copy the project

```bash
git clone <repo-url>
cd ippdd-app
```

If the project was given as a zip file, extract it first, then open a terminal inside the project folder.

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install JavaScript dependencies

```bash
npm install
```

### 4. Create the environment file

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
copy .env.example .env
```

### 5. Generate the Laravel app key

```bash
php artisan key:generate
```

### 6. Configure the database

Open `.env` and update this part:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ippdd_app
DB_USERNAME=root
DB_PASSWORD=
```

Create a database named `ippdd_app` in MySQL or phpMyAdmin.

For a default local XAMPP/Laragon setup, `DB_USERNAME=root` and blank `DB_PASSWORD=` usually works. If your MySQL has a password, put it there.

### 7. Run migrations

```bash
php artisan migrate
```

Optional, if you want sample data and the seeders are updated for your demo:

```bash
php artisan db:seed
```

### 8. Start the Laravel server

```bash
php artisan serve
```

Default URL:

```text
http://127.0.0.1:8000
```

### 9. Start the Vite frontend server

Open another terminal in the same project folder:

```bash
npm run dev
```

Keep both terminals open while developing.

## Build for Production

When deploying or preparing the app without the Vite dev server:

```bash
npm run build
```

Then run Laravel normally:

```bash
php artisan serve
```

## Moving the System to Another Computer

Do this on the new computer:

1. Copy the project folder, but do **not** copy `vendor/`, `node_modules/`, or `.env` unless you know exactly what you are doing.
2. Run:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

3. Create the database in MySQL/phpMyAdmin.
4. Update `.env` database settings.
5. Run:

```bash
php artisan migrate
npm run build
php artisan serve
```

During development, use:

```bash
npm run dev
```

## Uploading WFP Files

1. Open the app.
2. Go to **Upload WFP Data**.
3. Select the fiscal year.
4. Upload the WFP Excel file.
5. Review the parsed preview.
6. Confirm import.

The parser expects a sheet related to **STATUS AND MONITORING**. The exact sheet name can vary, but it should include words like `STATUS`, `MONITORING`, or `GUIDELINE`, and contain markers like `FUND 101`, `STATUS OF COMPLIANCE`, or `BUDGET ALLOCATION`.

If the WFP sheet for the selected year exists, performance indicators are also read. If not, the budget import can still continue.

## Useful Commands

Clear Laravel cache:

```bash
php artisan optimize:clear
```

Run migrations from scratch:

```bash
php artisan migrate:fresh
```

Run migrations from scratch with seed data:

```bash
php artisan migrate:fresh --seed
```

Check routes:

```bash
php artisan route:list
```

Build frontend assets:

```bash
npm run build
```

Start frontend dev server:

```bash
npm run dev
```

## Common Problems and Fixes

### `APP_KEY` error

Run:

```bash
php artisan key:generate
```

### Database connection error

Check `.env`:

```env
DB_DATABASE=ippdd_app
DB_USERNAME=root
DB_PASSWORD=
```

Make sure MySQL is running and the database exists.

### Vite page not loading or CSS not updating

Run:

```bash
npm run dev
```

Then hard refresh the browser:

```text
Ctrl + Shift + R
```

### Missing PHP extension error

Enable the missing extension in `php.ini`, then restart Apache/Laragon/XAMPP.

Common extensions needed:

```text
mbstring
xml
dom
xmlreader
xmlwriter
zip
pdo_mysql
```

### Uploaded Excel file does not parse

Check that:

- The file is `.xlsx` or `.xls`
- File size is below the upload limit
- The file has a STATUS/MONITORING sheet
- The sheet contains budget columns for Fund 101, 164, 161, 163, and total budget
- sample format find the file named : SAMPLE FORMAT XLSX FILE FOR UPLOADING.xlsx
### Graph does not show Fund 161 or Fund 163

The dashboard line chart already includes Fund 161 and Fund 163. If a fund still does not appear, the values for that fund are probably `0` for the selected years. Computers, in their petty literalness, refuse to draw money that does not exist.

## Maintenance Notes for the Next OJT Student

- `Budget.vue` should stay short. Do not put table calculations directly back into it.
- WFP parsing logic belongs in `app/Services/Wfp/`, not inside the controller.
- If a new fund type is added, update:
  - database migration/model fields
  - `resources/js/constants/wfp.js`
  - `resources/js/composables/useChartConfigs.js`
  - upload parser/persister under `app/Services/Wfp/`
- If a chart is repeated in two pages, keep it only where it gives a different insight. Duplicate charts are how dashboards become PowerPoint purgatory.
- Do not commit `.env`, `vendor/`, `node_modules/`, or generated cache files.

## Recommended Git Ignore

The project already has `.gitignore`, but make sure these are ignored:

```text
.env
/vendor
/node_modules
/public/build
/storage/*.key
/storage/logs/*.log
/bootstrap/cache/*.php
```
