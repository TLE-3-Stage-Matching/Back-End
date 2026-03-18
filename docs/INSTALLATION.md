# Installation & Deployment

This document covers local installation, production deployment, and the matching system (no external AI). For a quick summary, see the [README](../README.md).

## Installation (local)

### Prerequisites

- PHP 8.2+
- [Composer](https://getcomposer.org/)
- Node.js and npm (if using Vite for front-end assets)
- MySQL (or another database supported by Laravel)

### Steps

1. **Clone the repository** and enter the project directory.

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Environment file:** Copy `.env.example` to `.env`. If `.env.example` is missing, create `.env` with at least:
   - `APP_KEY` (generate with `php artisan key:generate`)
   - `DB_*` (database connection)
   - `JWT_SECRET` or the env vars used by [tymon/jwt-auth](https://github.com/tymon/jwt-auth) — run `php artisan jwt:secret` to generate.

4. **Configure `.env`:** Set `APP_URL`, database credentials, and any other required keys.

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

6. **Optional — seed database:** e.g. admin and dev users:
   ```bash
   php artisan db:seed
   ```
   (See [database/seeders/DatabaseSeeder.php](../database/seeders/DatabaseSeeder.php) and [AdminAndDevSeeder.php](../database/seeders/AdminAndDevSeeder.php).)

7. **Optional — front-end assets:** If the project uses Vite:
   ```bash
   npm install
   npm run build
   ```
   For development: `npm run dev`.

### Quick run

- **Full dev stack** (serve + queue + logs + vite): `composer run dev`
- **API only:** `php artisan serve`

---

## Deployment

### Environment

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use a strong `APP_KEY` (from `php artisan key:generate`).
- Configure database credentials and set `APP_URL` to the public base URL of the API.

### JWT

- Set `JWT_SECRET` (or the env vars used by tymon/jwt-auth). Ensure token TTL matches your front-end refresh strategy (see [API-v2: Refresh token](API-v2.md) and “Using JWT from front-ends”).

### API keys

- The v2 API requires an **API key** in the `X-API-KEY` header for all routes. Protected routes also require a **JWT** in `Authorization: Bearer <token>`.
- API keys can be created via the **Dev** and **Admin** endpoints (see [API-v2: Dev / Admin](API-v2.md)). Document for your team how keys are issued (e.g. one key per environment or per client).

### Migrations

- On deploy, run:
  ```bash
  php artisan migrate --force
  ```

### Optional

- **Queue worker:** If the application uses queues, run a worker (e.g. `php artisan queue:work`) or use a process manager (Supervisor, systemd).
- **Scheduler:** If the app uses the Laravel scheduler, add a cron entry: `* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`.

---

## Matching system

- **Implementation:** Student–vacancy matching is implemented in-app by [VacancyMatchingService](../app/Matching/VacancyMatchingService.php). It is **tag-based**: student tags (with weight 1–5) are compared to vacancy requirements (must_have / nice_to_have, importance 1–5). The formula uses sub-scores S_MH and S_NTH, a combined tag score, a must-have penalty, and clamps the final score between 0 and 100.
- **No external AI:** There is no external AI or LLM service to deploy. Matching is computed **on demand** when students or coordinators request vacancies with scores.
- **Sandbox (what-if) matching:** Students can simulate match results by sending temporary **skill**/**trait** tags to the sandbox endpoints. This does **not** write to the database; the student's profile stays intact.
- **Database:** The schema includes `ai_runs`, `ai_prompts`, and `ai_criteria_versions` for future batch or versioned runs; the live match scores are not persisted to `match_vacancy_scores` in the current flow.
- **If you add an external AI/LLM later:** Document the new service, env vars (e.g. API key, model ID), and any deployment steps (e.g. background jobs that call the model and write to `match_vacancy_scores`) in this file and in the README.
