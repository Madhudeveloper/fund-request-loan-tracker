# Fund Request & Loan Tracker

A small full-stack app where applicants request loans, upload supporting PDFs and track repayments, while admins review, approve or reject loans, record repayments and see a dashboard. An AI feature summarises uploaded documents and answers questions about a single loan.

> Technical assignment: Full-Stack Developer (Madasamy S)

```
loan-tracker/
├── backend/    Laravel 13 REST API (MySQL, Sanctum, queues, AI)
├── frontend/   React 18 + Vite app
├── docs/       Postman collection
└── README.md
```

## Tech stack

| Layer | Versions used |
|---|---|
| Backend | PHP 8.3, Laravel 13, Laravel Sanctum, MySQL 8, `smalot/pdfparser` |
| Frontend | React 18.3, Vite 5, React Router 6, Axios, Recharts, plain CSS |
| AI | `LlmClient` interface with Mock, Google Gemini and Anthropic clients |
| Queue | Laravel database queue |

Node 20+ and Composer 2 are required.

---

## Setup

### 1. Backend

```bash
cd backend
composer install
composer require laravel/sanctum smalot/pdfparser   # only if not already in composer.json
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database named `loan_tracker`, then set `DB_*` in `backend/.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=loan_tracker
DB_USERNAME=root
DB_PASSWORD=
```

Create the tables and seed the data, then start the server and the queue worker (two terminals):

```bash
php artisan migrate --seed
php artisan serve          # http://localhost:8000
php artisan queue:work     # processes the document summary jobs
```

Without a running queue worker, uploaded documents stay on `pending`.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env       # VITE_API_URL=http://localhost:8000/api
npm run dev                # http://localhost:5173
```

Restart `npm run dev` after changing `.env`.

### 3. Postman

Import `docs/loan-tracker.postman_collection.json`. Run **Login: Admin** or **Login: Applicant (Alice)** first. The token is saved automatically.

---

## Seeded accounts

All seeded users have the password `password`.

| Role | Email |
|---|---|
| Admin | `admin@example.com` |
| Applicant | `alice@example.com` |
| Applicant | `bob@example.com` |

The seeder also creates 10 sample loans in mixed statuses (pending, approved, rejected, closed). Approved loans have two repayments and closed loans are fully repaid.

---

## API overview

| Method | Endpoint | Access | Notes |
|---|---|---|---|
| POST | `/api/register`, `/api/login` | Public | Returns `{ token, user }` |
| POST | `/api/logout` | Logged in | Revokes the current token |
| GET | `/api/loans` | Applicant, Admin | Own loans (applicant) or all (admin). Paginated. Filters: `status`, `search` (purpose) |
| POST | `/api/loans` | Applicant | Amount ₹10,000 to ₹10,00,000, tenure 6 to 60 months |
| GET | `/api/loans/{id}` | Owner or Admin | Includes repayments and documents |
| PATCH | `/api/loans/{id}/status` | Admin | Approve or reject with remarks, only while pending |
| POST | `/api/loans/{id}/repayments` | Admin | Approved loans only. Cannot exceed the loan amount. Auto-closes when fully repaid |
| POST | `/api/loans/{id}/documents` | Owner | PDF only, max 5 MB. Queues the AI summary |
| POST | `/api/loans/{id}/ask` | Owner or Admin | AI loan assistant |
| GET | `/api/dashboard/summary` | Admin | Counts by status, disbursed, repaid, outstanding |

Status codes used: 200, 201, 401 (not logged in or bad login), 403 (not allowed), 404, 422 (validation), 503 (AI unavailable).

### Backend design notes

- **Form Requests** do validation and authorization. **API Resources** shape every response.
- **Policy** (`LoanRequestPolicy`): an applicant can only view, upload to or ask about their own loan. Admin-only actions are approve/reject, repayments and the dashboard.
- **Transaction + row lock** in the repayment endpoint, so two simultaneous payments cannot exceed the loan amount.
- **Atomic status update**: `UPDATE ... WHERE status = 'pending'`, so two admins cannot both decide the same loan.
- **No N+1 queries** on the list endpoint (`with('user')` and `withSum('repayments', 'amount')`).

---

## AI integration

### Provider and model

The app talks to an `LlmClient` interface. Pick the implementation with `AI_PROVIDER` in `backend/.env`:

| `AI_PROVIDER` | Client | Needs |
|---|---|---|
| `mock` (default) | `MockClient`: offline, regex-based | nothing |
| `gemini` | `GeminiClient` (Google Gemini API) | `GEMINI_API_KEY`, `GEMINI_MODEL` |
| `anthropic` | `AnthropicClient` (Claude API) | `ANTHROPIC_API_KEY`, `AI_MODEL` |

**Provider used for my demo:** _<fill in: mock, or Gemini `gemini-3.7-flash`, or Anthropic `claude-haiku-5-5`>_

If a key is missing, the app falls back to the mock client, so it always runs. The real HTTP request code for Gemini and Anthropic is in `backend/app/Services/AI/`. API keys live only in `backend/.env` and are never sent to the frontend.

### Feature A: document summary

1. The upload endpoint saves the PDF and returns right away with `summary_status = pending`.
2. A queued job (`SummarizeDocument`) extracts the PDF text, truncates it, and asks the model for JSON: `applicant_name`, `monthly_income`, `employer`, `key_risks[]`, `summary` (max 80 words).
3. The JSON is validated. If invalid, the model is called once more. If it is still invalid, or the model is unavailable, the status becomes `failed`. On success it becomes `completed`.

### Feature B: loan assistant

`POST /api/loans/{id}/ask` sends the model only that loan's own data: loan details, up to the latest 20 repayments and its document summaries. The route is authorized by the policy first, so a user cannot ask about someone else's loan. The system prompt tells the model to answer only from the supplied data.

### Errors, limits, prompts, logging

- **Timeouts, 429 and server errors** are caught and turned into a friendly message (HTTP 503 for the assistant, `failed` for documents).
- **Text limit:** at most **12,000 characters** of PDF text per request (about 3,000 tokens), set by `AI_MAX_CHARS`. Assistant context is capped at 4,000 characters. Model output is capped by `max_output_tokens`. This keeps cost and latency low and limits how much data is sent.
- **Prompts** are all in one place: `backend/app/Services/AI/Prompts.php`. The document prompt tells the model to ignore instructions found inside the document.
- **Token logging:** every request writes a `llm.usage` line (purpose, model, input and output tokens) to `storage/logs/laravel.log`.

### Estimated cost per request

| Request | Input tokens (approx.) | Output tokens (approx.) |
|---|---|---|
| Document summary | up to ~3,000 | ~300 |
| Assistant question | ~500 to 1,000 | ~100 |

Cost per request = (input tokens × input price) + (output tokens × output price), using the current price list of the provider and model you choose. With the Gemini free tier or the mock client the cost is **₹0**. _<fill in the estimate using the current pricing page of your chosen provider>_

---

## Frontend

Screens: Login and Register, My Loans with a New Loan Request form (including PDF upload), Loan Detail (loan info, repayments, documents with AI summary, assistant chat, add repayment for admins, approve/reject for admins), All Loans (admin: pagination, status filter, debounced search, remarks dialog) and Dashboard (admin: summary cards and a loans-by-status chart).

- Protected routes by role with React Router.
- One Axios instance (`src/api.js`) with a token interceptor. A 401 response clears the session and returns to login.
- Custom hooks: `useFetch` and `useDebounce`.
- Loading, empty and error states on every data screen.
- Responsive from 375 px to 1280 px.
- Amounts in INR format (₹1,00,000), dates as DD-MM-YYYY.
- No secrets in frontend code.

---

## Assumptions

- Only applicants can create loans. Admins review them.
- Repayment reference numbers are unique.
- "Outstanding" means loan amount minus repayments. Disbursed means loans with status approved or closed.
- Search matches the loan purpose.
- Documents are stored on the private `local` disk and are not served publicly.

## Use of AI coding tools

I used an AI assistant (Claude) while building this project, including for generating and debugging the Laravel backend, the React frontend, the AI integration, the Postman collection and this README. I ran, tested and reviewed the code myself. _<edit this section to describe exactly what you wrote yourself and what the assistant helped with. Be ready to explain every file in the follow-up call.>_

## Unfinished items and known issues

- Automated tests (PHPUnit/Pest and React Testing Library) are not included.
- No Docker or Laravel Sail setup.
- The frontend is JavaScript, not TypeScript, and uses a small custom `useFetch` hook instead of TanStack Query.
- The optional Node.js (Express) dashboard service is not built.
- The mock client uses simple pattern matching, so its extracted fields can be rough. A real model gives better results.
- Scanned (image-only) PDFs are marked `failed` because there is no OCR.
- Documents stay `pending` unless a queue worker is running.
- No UI to download an uploaded document.
- API rate limiting is only applied to login.

## What I would improve with more time

- Add feature tests for login, loan creation, approval and repayments, and a React Testing Library test.
- Add Docker/Sail so the whole app starts with one command.
- Move the frontend to TypeScript and TanStack Query.
- Add OCR for scanned PDFs, secure document download, and an audit log of status changes.
- Add token expiry and refresh, and stricter rate limits.
- Add an OpenAPI specification and CI that runs the tests.

## Security notes

- Never commit `.env` or any real API key. Only `.env.example` files are committed.
- Use sample PDFs with made-up details only.
