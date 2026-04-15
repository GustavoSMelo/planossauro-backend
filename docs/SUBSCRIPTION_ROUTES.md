# Subscription Routes

Base URL: `http://localhost:8080/api`

**Note:** All subscription routes require authentication via Sanctum Bearer token and are rate limited to 100 requests per minute.

---

## POST /subscription/assign/free/{userUUID}

Assign free plan to a user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/subscription/assign/free/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/subscription/assign/free/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Free plan assigned successfully",
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440003",
    "status": "active",
    "started_at": "2026-04-15T10:00:00Z",
    "expires_at": null,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/subscription/assign/free/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## POST /subscription/assign/{userUUID}

Assign a paid plan to a user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/subscription/assign/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/subscription/assign/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan_uuid` | string (UUID) | Yes | The plan's unique identifier |
| `payment_method_id` | string | Yes | Stripe payment method ID |

### Body Request Example (JSON)

```json
{
  "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
  "payment_method_id": "pm_card_visa"
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "Plan assigned successfully",
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
    "status": "active",
    "started_at": "2026-04-15T10:00:00Z",
    "expires_at": "2026-05-15T10:00:00Z",
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/subscription/assign/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
    "payment_method_id": "pm_card_visa"
  }'
```

---

## PUT /subscription/{userUUID}

Update/assign subscription for user (alternative endpoint).

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/subscription/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/subscription/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `plan_uuid` | string (UUID) | Yes | The plan's unique identifier |
| `payment_method_id` | string | No | Stripe payment method ID |

### Body Request Example (JSON)

```json
{
  "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
  "payment_method_id": "pm_card_visa"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Subscription updated successfully",
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
    "status": "active",
    "started_at": "2026-04-15T10:00:00Z",
    "expires_at": "2026-05-15T10:00:00Z",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/subscription/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440004"
  }'
```

---

## PATCH /subscription/status/update/{subscriptionId}

Update subscription status.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/subscription/status/update/{subscriptionId}` |
| **Full URL** | `http://localhost:8080/api/subscription/status/update/{subscriptionId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateSubscriptionID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscriptionId` | string (UUID) | Yes | The subscription's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | Yes | New status (active, paused, cancelled, expired) |

### Body Request Example (JSON)

```json
{
  "status": "paused"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Subscription status updated successfully",
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "status": "paused",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/subscription/status/update/990e8400-e29b-41d4-a716-446655440005" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paused"
  }'
```

---

## GET /subscription/{userUUID}

Get user's subscription.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/subscription/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/subscription/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "plan": {
      "uuid": "880e8400-e29b-41d4-a716-446655440004",
      "name": "Professional Plan",
      "price": 49.90,
      "billing_period": "monthly"
    },
    "status": "active",
    "started_at": "2026-04-15T10:00:00Z",
    "expires_at": "2026-05-15T10:00:00Z",
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

**No Subscription (404 Not Found)**

```json
{
  "message": "No active subscription found"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/subscription/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## GET /subscription/dashboard/{userUUID}

Get subscription dashboard data for user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/subscription/dashboard/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/subscription/dashboard/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "status": "active",
    "plan": {
      "name": "Professional Plan",
      "price": 49.90
    },
    "expires_at": "2026-05-15T10:00:00Z"
  },
  "usage": {
    "plannings_used": 5,
    "plannings_limit": -1,
    "planning_hours_used": 45,
    "planning_hours_limit": -1
  },
  "statistics": {
    "total_plannings": 5,
    "active_plannings": 3,
    "archived_plannings": 2,
    "total_spent": 149.70
  }
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/subscription/dashboard/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PATCH /subscription/{planningType}/{subscriptionId}

Add planning usage to subscription.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/subscription/{planningType}/{subscriptionId}` |
| **Full URL** | `http://localhost:8080/api/subscription/{planningType}/{subscriptionId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateSubscriptionID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `planningType` | string | Yes | Type of planning (e.g., "hour", "basic", "premium") |
| `subscriptionId` | string (UUID) | Yes | The subscription's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Planning usage recorded successfully",
  "usage": {
    "planning_type": "hour",
    "quantity": 1,
    "total_used": 46,
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/subscription/hour/990e8400-e29b-41d4-a716-446655440005" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PUT /subscription/change/payment/method

Change subscription payment method.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/subscription/change/payment/method` |
| **Full URL** | `http://localhost:8080/api/subscription/change/payment/method` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBodyUserID` |
| **Rate Limit** | 100 requests per minute |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | string (UUID) | Yes | The user's unique identifier |
| `payment_method_id` | string | Yes | New Stripe payment method ID |

### Body Request Example (JSON)

```json
{
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "payment_method_id": "pm_card_visa"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Payment method updated successfully"
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/subscription/change/payment/method" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "payment_method_id": "pm_card_visa"
  }'
```

---

## PUT /subscription/change/subscription/plan

Change subscription plan.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/subscription/change/subscription/plan` |
| **Full URL** | `http://localhost:8080/api/subscription/change/subscription/plan` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBodyUserID` |
| **Rate Limit** | 100 requests per minute |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | string (UUID) | Yes | The user's unique identifier |
| `new_plan_uuid` | string (UUID) | Yes | New plan's unique identifier |

### Body Request Example (JSON)

```json
{
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "new_plan_uuid": "880e8400-e29b-41d4-a716-446655440004"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Subscription plan changed successfully",
  "subscription": {
    "uuid": "990e8400-e29b-41d4-a716-446655440005",
    "plan_uuid": "880e8400-e29b-41d4-a716-446655440004",
    "status": "active",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/subscription/change/subscription/plan" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "new_plan_uuid": "880e8400-e29b-41d4-a716-446655440004"
  }'
```

---

## DELETE /subscription/cancel/{subscriptionId}

Cancel a subscription.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | DELETE |
| **Path** | `/subscription/cancel/{subscriptionId}` |
| **Full URL** | `http://localhost:8080/api/subscription/cancel/{subscriptionId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateSubscriptionID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subscriptionId` | string (UUID) | Yes | The subscription's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Subscription cancelled successfully"
}
```

### Curl Example

```bash
curl -X DELETE "http://localhost:8080/api/subscription/cancel/990e8400-e29b-41d4-a716-446655440005" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```
