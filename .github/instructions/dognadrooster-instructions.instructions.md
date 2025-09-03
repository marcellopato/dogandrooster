---
applyTo: '**'
---
# Ecom Volatile Pricing Take‑Home — Candidate Instructions

## Product goal & scenario (read first)

Build a thin, production‑minded slice of an e‑commerce checkout for precious metals with **volatile, spot‑indexed prices** and **thin margins**. Your slice must:

- Produce **locked price quotes** valid for a short window.
- **Refuse stale quotes** when the market moves beyond a tolerance band.
- **Fail fast** when the fulfillment partner has insufficient stock (mocked API).
- Create orders in an **idempotent, transactional** way and update status via **signed payment webhooks**.
- Expose a **minimal UI** that makes errors understandable to non‑technical users and shows a **countdown** until the quote expires.

**Timebox:** 2 hours. Use public docs/tools. If you use AI or third‑party snippets, cite briefly in your README.

**Starter:** This repo is a starter with migrations/seeders, controllers/service stubs, mock fulfillment endpoints, CI, and a minimal Vue page. Your job is to implement the TODOs and make tests pass. Please review README.md for more details.

---

## Deliverables

1. Working code that implements the requirements below.
2. Tests green in CI.
3. A short `README.md` covering setup, assumptions, trade‑offs, concurrency/idempotency decisions, and “what you’d do with more time.”

---

## Functional requirements

1. **Quote** — `POST /api/quote { sku, qty }`

   - Compute `unit_price_cents = spot_per_oz_cents * weight_oz + premium_cents` using **only integer math**.
   - Persist a `price_quote` with: `basis_spot_cents`, `basis_version` (`spot_prices.id`), `quote_expires_at = now()+5min`, `tolerance_bps` (default 50).
   - Respond `{ quote_id, unit_price_cents, quote_expires_at }` (ISO‑8601, UTC).

2. **Checkout** — `POST /api/checkout { quote_id }` with header `Idempotency-Key`

   - **Reject** if quote expired → `409 { error: "REQUOTE_REQUIRED" }`.
   - **Reject** if absolute basis‑points move between **basis** and **current** spot exceeds `tolerance_bps` → `409 { error: "REQUOTE_REQUIRED" }`.
   - **Check fulfillment inventory** via mock API; if insufficient or non‑200 → `409 { error: "OUT_OF_STOCK" }`.
   - On success, **within a DB transaction**: create `orders` (status `pending`) and `order_lines` using the **quoted** unit price/qty (no recalculation); generate a `payment_intent_id`.
   - Enforce **idempotency**: same `Idempotency-Key` returns the same order.

3. **Payment webhooks** — `POST /api/webhooks/payments`

   - Verify HMAC with `PAYMENT_WEBHOOK_SECRET`.
   - `payment_authorized` → `orders.status = authorized`.
   - `payment_captured` allowed only from `authorized` → `captured`.
   - Invalid signature or unknown intent → `400` and **no state change**.

4. **Mock fulfillment inventory**

   - `GET /api/mock-fulfillment/availability/{sku}` → `{ available_qty }` (used by your checkout flow).
   - `POST /api/mock-fulfillment/availability { sku, available_qty }` to set stock for tests.

---

## UI/UX requirements (implement in the provided `QuoteDemo.vue`)

- **Countdown timer:** show `mm:ss` remaining until `quote_expires_at`, ticking every second. When it hits `00:00`, disable checkout and show **“Quote expired — get a new quote.”**
- **Friendly errors:** map backend error codes to plain language:

  - `REQUOTE_REQUIRED` → “Prices moved while you were checking out. Get a fresh quote to continue.”
  - `OUT_OF_STOCK` → “This item just sold out at our fulfillment partner. Try a smaller quantity or another product.”
  - `invalid_signature` / `unknown_intent` → “We couldn’t confirm payment with the provider. Please retry.”

- **Inline feedback:** do not show raw JSON; use a banner or toast with a primary action (re‑quote button).
- **Loading/disabled states:** disable the checkout button during the request; re‑enable on completion.
- **Accessibility:** error/success banners should be focusable and announced (e.g., `role="alert"`).

---

## Non‑functional requirements

- **Money:** integer **cents** only; no floats/decimals in pricing or checkout services.
- **Time:** persist and compare in **UTC**; treat `now() == quote_expires_at` as **expired**.
- **Concurrency:** lock quote during checkout; wrap checks + writes in a **DB transaction**.
- **Errors:** explicit 4xx with a single `error` string for business errors; avoid 500s for expected flows.
- **Observability:** log fulfillment calls and webhook results at INFO.

---

## Acceptance tests (must pass)

The starter includes skeletons. Make these green and **add** the two marked tests.

1. Integer money — `Pricing/IntegerMoneyTest.php` (unit price is integer).
2. Quote expiry — `Checkout/QuoteExpiryTest.php` → `409 REQUOTE_REQUIRED`.
3. Tolerance breach — `Checkout/ToleranceBreachTest.php` → `409 REQUOTE_REQUIRED`.
4. Idempotency — `Checkout/IdempotencyTest.php` → same `order_id` for duplicate key.
5. Inventory check — `Checkout/InventoryCheckTest.php` → `409 OUT_OF_STOCK`.
6. Webhook (valid signature) — `Webhooks/SignatureTest.php` → `authorized`.
7. **You add:** `Webhooks/InvalidSignatureTest.php` → `400` and no state change.
8. **You add:** `Checkout/TotalsIntegrityTest.php` → `orders.total_cents == sum(order_lines.subtotal_cents)` and `subtotal == unit * qty`.

### Manual UI checks (document in README)

- **Countdown behavior:** shows `mm:ss`; at `00:00`, checkout disabled and re‑quote action visible.
- **Error mapping:** force `REQUOTE_REQUIRED` and `OUT_OF_STOCK`; friendly text appears with re‑quote CTA.
- **Loading state:** checkout button disabled while posting.

CI must pass: Pint, Larastan (level 6), PHPUnit Feature + Unit.

---

## Scoring rubric (100 pts)

- **Price safety & correctness (25):** integer cents, lock & tolerance, edge boundaries.
- **Checkout resilience (25):** transaction, idempotency, inventory validation, explicit errors.
- **Integrations & webhooks (15):** HMAC verify, legal transitions, failure handling.
- **Tests (15):** coverage of critical paths; two added tests are solid and deterministic.
- **Code quality (10):** small pure functions, clear naming, seams for testing.
- **Docs/README (10):** crisp setup, assumptions, reasoning.

**Disqualifiers:** floats/decimals in pricing paths; re‑pricing server totals differently than the quote; missing idempotency; swallowing webhook signature failures; returning 200 on business errors; no transaction on checkout.

---

## Submission checklist

- Repo link
- All tests green in CI
- README with setup, trade‑offs, concurrency/idempotency notes, and improvements
- 3–5 min Loom walkthrough

## Implementation details IMPORTANT!!!

- We are on Windows running Docker, so always enter on the container's shell using `docker exec -it <container_name> bash` to execute `php artisan <command>`
- **ALWAYS execute Laravel commands inside the container:** `docker exec dogandrooster-laravel.test-1 php artisan <command>`
- Always create a new branch for your work.
- Delegate code review to CoPilot.
- Follow the existing code style and conventions.
- Write clear, concise commit messages.
- Include tests for new features and bug fixes.
- Document any significant changes in the README.
- Use descriptive branch names.
- Use this instructions as a checklist for your work.