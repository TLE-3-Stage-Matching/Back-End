# Expanding the project

This document lists **schema areas** that exist in the [ERD](schema.dbml) but are **unused or only partly used** in the current app, with recommendations for next steps. The README has a short summary; this file is the full reference.

---

## 1. AI versioning and batch runs

**Schema:** `ai_prompts`, `ai_runs`, `ai_criteria_versions`, `ai_criteria_rules`, `match_vacancy_scores`, `match_vacancy_factors`.

**Current state:** Matching is computed **on demand** by [VacancyMatchingService](../app/Matching/VacancyMatchingService.php). Scores are not persisted to `match_vacancy_scores`. The schema supports batch “AI runs” linked to a criteria version and optional prompt; `match_vacancy_factors` can store a per-factor breakdown (label, impact, polarity, tag_id).

**Recommendations:**

- Implement **batch matching jobs** that run for a cohort of students and/or vacancies, write results to `match_vacancy_scores` and `match_vacancy_factors`, and link each run to `ai_runs` (and optionally `ai_criteria_versions`).
- Expose **criteria versioning** in the API or admin UI: create/update `ai_criteria_versions` and `ai_criteria_rules` (e.g. weight, min_required, penalty_if_missing per `ai_feature_type`) and allow choosing a version when running batch matching.
- Optionally add an **`ai_models`** table and link `ai_runs.model_id` to it if you introduce multiple models (e.g. rule-based vs external LLM).

**Models:** [AiRun](../app/Models/AiRun.php), [AiPrompt](../app/Models/AiPrompt.php), [AiCriteriaVersion](../app/Models/AiCriteriaVersion.php), [AiCriteriaRule](../app/Models/AiCriteriaRule.php), [MatchVacancyScore](../app/Models/MatchVacancyScore.php), [MatchVacancyFactor](../app/Models/MatchVacancyFactor.php).

---

## 2. Messaging system

**Schema:** `conversations` (with `conversation_type`: vacancy_chat, student_admin, admin_company), `messages`.

**Current state:** Models exist ([Conversation](../app/Models/Conversation.php), [Message](../app/Models/Message.php)) and are related from User, Company, and Vacancy. There are **no API endpoints** for creating conversations or sending messages.

**Recommendations:**

- Add a **REST (or WebSocket) API** for: create conversation (with type and context: vacancy_id, student_user_id, etc.), list conversations for the current user, list messages in a conversation, send message. Enforce permissions by role (e.g. vacancy_chat: student + company for that vacancy; student_admin: coordinator/admin + student).
- Consider **notifications** (e.g. email or in-app) when a new message is sent.

**Models:** [Conversation](../app/Models/Conversation.php), [Message](../app/Models/Message.php).

---

## 3. Bias alerts and match overrides

**Schema:** `bias_alerts` (alert_type, must_have_snapshot, remaining_candidates, bias_tip, status, coordinator_user_id), `match_overrides` (coordinator_user_id, student_user_id, company_id, vacancy_id, action, reason, expires_at).

**Current state:** Models exist ([BiasAlert](../app/Models/BiasAlert.php), [MatchOverride](../app/Models/MatchOverride.php)). There are **no controllers or routes** for these in the codebase.

**Recommendations:**

- **Bias alerts:** Implement detection (e.g. after a batch run or when vacancy requirements change) that creates `bias_alerts` when must-haves heavily reduce candidate count or match a known bias pattern. Add a coordinator API to list, filter, and update status/resolution.
- **Match overrides:** Coordinator API to create/list overrides (e.g. “always show this student for this vacancy” or “hide this match”) with reason and optional expiry; apply overrides when computing or returning match results.

**Models:** [BiasAlert](../app/Models/BiasAlert.php), [MatchOverride](../app/Models/MatchOverride.php).

---

## 4. Manual placements

**Schema:** `manual_placements` (student_user_id, company_name, contact_email, description, start_date, status, coordinator_user_id, notes).

**Current state:** Model exists ([ManualPlacement](../app/Models/ManualPlacement.php)). There is **no API**.

**Recommendations:**

- Coordinator (or admin) API to **create, list, and update** manual placements for students placed outside the platform (company name, contact, description, start date, status). Useful for reporting and history.

**Models:** [ManualPlacement](../app/Models/ManualPlacement.php).

---

## 5. Already partially used (extend further)

### Match flags

[StudentFlagController](../app/Http/Controllers/Api/Student/StudentFlagController.php) and [CoordinatorFlagController](../app/Http/Controllers/Api/Coordinator/CoordinatorFlagController.php) exist. You can extend with more statuses, notifications, or linking to `bias_alerts`.

### Student match choices

Shortlisted / requested / approved / rejected / withdrawn are in use ([StudentMatchChoiceController](../app/Http/Controllers/Api/Student/StudentMatchChoiceController.php), [CompanyMatchChoiceController](../app/Http/Controllers/Api/Company/CompanyMatchChoiceController.php), [CoordinatorMatchChoiceController](../app/Http/Controllers/Api/Coordinator/CoordinatorMatchChoiceController.php)). You can add workflow (e.g. company sees “requested” and can approve/reject), or link `source_run_id` / `source_match_score_id` when batch runs are implemented so choices reference a specific run.

**Models:** [MatchFlag](../app/Models/MatchFlag.php), [StudentMatchChoice](../app/Models/StudentMatchChoice.php).

---

## Schema reference

Full DBML: [schema.dbml](schema.dbml). Entity overview: [DATA-MODEL.md](DATA-MODEL.md).
