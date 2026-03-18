# Back-End TLE3

Laravel backend for **student–vacancy matching**: students, companies, and coordinators manage profiles, vacancies, and match scores via the v2 API.

## Table of contents

- [About](#about)
- [Tech stack](#tech-stack)
- [Data model](#data-model)
- [Installation & deployment](docs/INSTALLATION.md)
- [Matching system](#matching-system)
- [Edge cases](docs/EDGE-CASES.md)
- [API & documentation](docs/API-v2.md)
- [Testing](docs/TESTS.md)
- [Expanding the project](docs/EXPANDING.md)
- [License](#license)

---

## About

This project is a Laravel API that supports **student–vacancy matching**: students maintain profiles and get match scores for vacancies; companies publish vacancies and manage match choices; coordinators manage companies, users, and student–coordinator assignments. Roles: **student**, **company**, **coordinator**.

## Tech stack

- PHP 8.2+, Laravel 12, JWT ([tymon/jwt-auth](https://github.com/tymon/jwt-auth)), MySQL (or configured DB). Optional: Node/npm (Vite), queue worker.

## Data model

The database is described in DBML; entity groups and relationships are documented in [docs/DATA-MODEL.md](docs/DATA-MODEL.md). You can view an ERD by importing [docs/schema.dbml](docs/schema.dbml) into [dbdiagram.io](https://dbdiagram.io).

## Installation & deployment

1. Clone the repo and run `composer install`.
2. Copy `.env.example` to `.env`, run `php artisan key:generate` and `php artisan jwt:secret`, configure DB and `APP_URL`, then `php artisan migrate` (optionally `php artisan db:seed`).
3. Run the app with `composer run dev` or `php artisan serve`.

**Full guide:** [docs/INSTALLATION.md](docs/INSTALLATION.md) (local setup, deployment, env, JWT, API keys, matching system).

## Matching system

Student–vacancy matching is **rule-based** and computed **on demand**: [VacancyMatchingService](app/Matching/VacancyMatchingService.php) scores students against vacancies using tags (must-have / nice-to-have, importance and weight 1–5). There is **no external AI service**; the database supports AI run metadata (e.g. criteria versions) for future batch or versioned runs. See [docs/INSTALLATION.md](docs/INSTALLATION.md) for deployment notes.

## Edge cases

Matching and validation edge cases (e.g. vacancy with no must-haves, score clamping, tag limits, auth errors) are listed with short explanations in [docs/EDGE-CASES.md](docs/EDGE-CASES.md). See also [docs/TESTS.md](docs/TESTS.md) and [docs/API-v2.md](docs/API-v2.md).

## API & documentation

- **Base URL:** `https://<your-api-host>/api/v2`
- **Auth:** API key in `X-API-KEY` header for all requests; JWT in `Authorization: Bearer <token>` for protected routes.
- **Roles:** Coordinator (register, companies, users, vacancies, match scores, assignments); Company (own company, profile, vacancies, comments); Student (profile, experiences, preferences, tags, languages, favorites, saved vacancies, vacancy matching).

If you open your `APP_URL` in a browser, you’ll find **extensive API documentation** and guidance for **API key generation** for local/dev usage.

**Full v2 API reference:** [docs/API-v2.md](docs/API-v2.md).

## Testing

- `composer test` or `php artisan test` — run the full suite.
- `php artisan test --testsuite=Unit` / `--testsuite=Feature` — run by suite.
- `php artisan test --filter=test_name` — run tests matching a name.
- `php artisan test --coverage` — coverage report (requires Xdebug or PCOV).

**Full test catalog and coverage map:** [docs/TESTS.md](docs/TESTS.md).

## Expanding the project

Schema areas that are unused or partly used and good candidates for expansion:

- **AI versioning and batch runs** — `ai_prompts`, `ai_runs`, `ai_criteria_versions`, `match_vacancy_scores` / `match_vacancy_factors`
- **Messaging** — `conversations`, `messages` (vacancy_chat, student_admin, admin_company)
- **Bias alerts and match overrides** — `bias_alerts`, `match_overrides` (coordinator APIs)
- **Manual placements** — `manual_placements` (coordinator API for placements outside the platform)
- **Match flags and student match choices** — extend statuses, workflow, link to batch runs

**Full recommendations and next steps:** [docs/EXPANDING.md](docs/EXPANDING.md).

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
