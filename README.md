# Bumpa Backend Assessment

This is an API-only Laravel implementation for the Bumpa backend assessment. It tracks purchase-driven achievements, unlocks badges from achievement counts, awards NGN 300 cashback on each newly unlocked badge, and exposes a user achievement progress endpoint.

There is no frontend or Vite process in this project.

## Requirements

- PHP 8.3+
- Composer
- Docker and Docker Compose

## Docker Setup

Start the application and Postgres database:

```bash
cp .env.example .env
docker compose up --build
```

The app is available at:

```text
http://localhost:7000
```

Inside Docker, Laravel listens on port `8000`; Docker Compose maps that to port `7000` on the host.

The `app` service waits for Postgres to become healthy, then runs:

```bash
php artisan migrate --force
php artisan serve --host=0.0.0.0 --port=8000
```

Run seeders inside the app container:

```bash
docker compose exec app php artisan db:seed
```

Reset and seed the database:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Run tests in Docker:

```bash
docker compose exec app php artisan test
```

## Local Development

Start only the database:

```bash
docker compose up database
```

Install dependencies and prepare the app:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Start the local Laravel server:

```bash
composer dev
```

Run tests locally:

```bash
php artisan test
```

## Database Seeding

Achievement and badge definitions are seeded from `App\Support\RewardDefinitions`.

Seeded purchase achievements:

- `First Purchase`
- `5 Purchases`
- `10 Purchases`
- `20 Purchases`

Seeded badges:

- `Beginner`, requires 1 achievement
- `Intermediate`, requires 4 achievements
- `Advanced`, requires 8 achievements
- `Master`, requires 10 achievements

## API

After running `php artisan migrate:fresh --seed`, demo users are available for manual testing. Fetch the user list first, copy an `id`, then call the achievement progress endpoint for that user.

### List Users

```http
GET /users
```

Example response:

```json
{
  "data": [
    {
      "id": 1,
      "name": "New Customer",
      "email": "new.customer@example.com",
      "achievements_count": 0,
      "badges_count": 0,
      "purchases_count": 0,
      "cashback_transactions_count": 0
    }
  ]
}
```

### Get User Achievement Progress

```http
GET /users/{user}/achievements
```

Example manual test flow:

```bash
curl http://localhost:7000/users
curl http://localhost:7000/users/1/achievements
```

Example response for a new user:

```json
{
  "unlocked_achievements": [],
  "next_available_achievements": ["First Purchase"],
  "current_badge": "",
  "next_badge": "Beginner",
  "remaining_to_unlock_next_badge": 1
}
```

Example response after `First Purchase` and `5 Purchases` are unlocked:

```json
{
  "unlocked_achievements": ["First Purchase", "5 Purchases"],
  "next_available_achievements": ["10 Purchases"],
  "current_badge": "Beginner",
  "next_badge": "Intermediate",
  "remaining_to_unlock_next_badge": 2
}
```

Missing users return Laravel's normal `404` response.

## Event Workflow

The reward workflow is event-driven:

```text
PurchaseRecorded
  -> EvaluatePurchaseAchievements
  -> AchievementService unlocks achievement
  -> AchievementUnlocked
  -> EvaluateBadges
  -> BadgeService unlocks badge
  -> BadgeUnlocked
  -> AwardBadgeCashback
  -> CashbackPaymentProvider sends cashback
```

`AchievementUnlocked` exposes:

- `achievement_name`
- `user`

`BadgeUnlocked` exposes:

- `badge_name`
- `user`

Laravel event discovery registers the listeners automatically.

## Payment Provider

Cashback is integrated through `App\Contracts\Payments\CashbackPaymentProvider`.

The active provider is configured in `config/cashback.php`:

```php
'provider' => env('CASHBACK_PAYMENT_PROVIDER', 'payment_mock'),
```

The default provider is `payment_mock`, a local mock implementation that returns a successful Flutterwave-like transfer response without calling an external API or requiring secrets:

```env
CASHBACK_PAYMENT_PROVIDER=payment_mock
PAYMENT_MOCK_MODE=local
```

Use Flutterwave only when real v3 credentials are available:

```env
CASHBACK_PAYMENT_PROVIDER=flutterwave
FLUTTERWAVE_SECRET_KEY=
FLUTTERWAVE_BASE_URL=https://api.flutterwave.com/v3
FLUTTERWAVE_CALLBACK_URL=
```

The Flutterwave implementation uses the v3 transfer API:

```http
POST /transfers
Authorization: Bearer {FLUTTERWAVE_SECRET_KEY}
```

The cashback listener sends NGN 300 as `30000` kobo. User bank details come from the user's `UserPayoutAccount` record.

## Idempotency

The implementation uses database constraints and deterministic keys to avoid duplicate rewards:

- `achievement_user` has a unique `user_id, achievement_id` constraint.
- `badge_user` has a unique `user_id, badge_id` constraint.
- `cashback_transactions` has a unique `user_id, badge_id` constraint.
- Cashback idempotency keys use `badge-cashback:{user_id}:{badge_id}`.
- If a cashback transaction is already `successful`, replayed `BadgeUnlocked` events do not call the payment provider again.

Failed cashback attempts are recorded with failure details. If a user has no payout account, the cashback transaction is marked `failed` instead of crashing the event workflow.

## Known Assumptions

- Purchases are represented by a minimal `purchases` table because the assessment does not include a full commerce module.
- Badge cashback is paid to one payout account per user.
- `UserPayoutAccount` stores the bank code and account number required by the payment provider.
- Cashback is recorded synchronously in this assessment implementation. In production, the listener would usually be queued.
- Tests fake the payment provider and do not send real money.

## Verification

Run the full test suite:

```bash
php artisan test
```

Fresh Docker verification:

```bash
cp .env.example .env
docker compose up --build
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
```
