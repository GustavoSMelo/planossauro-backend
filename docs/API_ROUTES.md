# Planossauro Backend API Documentation

## Overview

The Planossauro Backend API provides RESTful endpoints for managing users, planning sessions, subscriptions, payments, and authentication. The API is built with Laravel and uses Sanctum for authentication.

**Base URL:** `/api`

---

## Authentication

Most endpoints require authentication using **Laravel Sanctum** tokens. Authenticated routes use the `auth:sanctum` middleware.

### Authentication Flow

1. **GitHub Authentication**
   - Get access token: `GET /token/github/{code}`
   - Authenticate: `GET /auth/github/{token}`

2. **Google Authentication**
   - Authenticate: `GET /auth/google/{token}`

3. **Logout**
   - `DELETE /logout/{userUUID}` (requires auth)

---

## Rate Limiting

API endpoints are protected with rate limiting middleware (`throttle`). Limits vary by endpoint:

| Limit | Endpoints |
|-------|-----------|
| 10 req/min | Logout |
| 20 req/min | Health check, User creation, Support emails, Auth endpoints |
| 40 req/min | Plans listing |
| 100 req/min | Planning, PlanningHour, Subscription, Payment History |

---

## Middleware Reference

| Middleware | Description |
|------------|-------------|
| `auth:sanctum` | Laravel Sanctum token authentication |
| `throttle:X,1` | Rate limiting (X requests per minute) |
| `ValidateUserTokenByRoute` | Validates user token from route parameters |
| `ValidateUserTokenByBody` | Validates user token from request body |
| `ValidateUserTokenByBodyUserID` | Validates user token and user ID from request body |
| `ValidatePlanningID` | Validates planning UUID format and existence |
| `ValidateSubscriptionID` | Validates subscription ID format and existence |
| `ValidatePaymentHistoryID` | Validates payment history ID format and existence |

---

## Endpoints

### Health Check

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/` | No | Health check endpoint |
| `GET` | `/health` | No | Health check endpoint |

#### Examples

```bash
# Health check (root)
curl -X GET "http://localhost:8000/api/"

# Health check
curl -X GET "http://localhost:8000/api/health"
```

---

### Users

**Base Path:** `/user`

| Method | Endpoint | Auth | Middleware | Description |
|--------|----------|------|------------|-------------|
| `POST` | `/user` | No | - | Create a new user |
| `GET` | `/user/{userUUID}` | Yes | ValidateUserTokenByRoute | Get user details by UUID |
| `PUT` | `/user/{userUUID}` | Yes | ValidateUserTokenByRoute | Update user information |
| `DELETE` | `/user/{userUUID}` | Yes | ValidateUserTokenByRoute | Delete user |
| `GET` | `/user/github/{githubEmail}` | No | - | Find user by GitHub email |
| `GET` | `/user/google/{googleEmail}` | No | - | Find user by Google email |
| `POST` | `/user/resend/validationcode` | Yes | ValidateUserTokenByBody | Resend validation email |
| `PATCH` | `/user/validate/{userUUID}` | Yes | ValidateUserTokenByRoute | Validate user account |
| `PATCH` | `/user/restore/{userUUID}` | Yes | ValidateUserTokenByRoute | Restore soft-deleted user |
| `PATCH` | `/user/unlink/{userUUID}` | Yes | ValidateUserTokenByRoute | Unlink social accounts |

#### Examples

```bash
# Create a new user
curl -X POST "http://localhost:8000/api/user" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "securepassword123"
  }'

# Get user by UUID
curl -X GET "http://localhost:8000/api/user/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Update user
curl -X PUT "http://localhost:8000/api/user/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Updated"
  }'

# Delete user
curl -X DELETE "http://localhost:8000/api/user/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Find user by GitHub email
curl -X GET "http://localhost:8000/api/user/github/john%40github.com"

# Find user by Google email
curl -X GET "http://localhost:8000/api/user/google/john%40gmail.com"

# Resend validation email
curl -X POST "http://localhost:8000/api/user/resend/validationcode" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}"
  }'

# Validate user account
curl -X PATCH "http://localhost:8000/api/user/validate/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Restore soft-deleted user
curl -X PATCH "http://localhost:8000/api/user/restore/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Unlink social accounts
curl -X PATCH "http://localhost:8000/api/user/unlink/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"
```

---

### Planning

**Base Path:** `/planning`  
**Auth Required:** Yes  
**Rate Limit:** 100 req/min

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| `POST` | `/planning/search/{userUUID}` | ValidateUserTokenByRoute | Search plannings by filters |
| `GET` | `/planning/paginate/{userUUID}` | ValidateUserTokenByRoute | Get paginated planning list |
| `POST` | `/planning` | ValidateUserTokenByBody | Create a new planning |
| `GET` | `/planning/show/{uuid}` | ValidatePlanningID | Get planning details |
| `PUT` | `/planning/{uuid}` | ValidateUserTokenByBodyUserID | Update planning |
| `DELETE` | `/planning/{uuid}` | ValidatePlanningID | Delete planning |
| `PATCH` | `/planning/archive/{uuid}` | ValidatePlanningID | Archive planning |
| `PATCH` | `/planning/unarchive/{uuid}` | ValidatePlanningID | Unarchive planning |
| `POST` | `/planning/create` | - | Create planning (legacy) |

#### Examples

```bash
# Search plannings by filters
curl -X POST "http://localhost:8000/api/planning/search/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "active",
    "type": "monthly"
  }'

# Get paginated planning list
curl -X GET "http://localhost:8000/api/planning/paginate/{userUUID}?page=1&per_page=10" \
  -H "Authorization: Bearer {sanctum_token}"

# Create a new planning
curl -X POST "http://localhost:8000/api/planning" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "title": "My Planning",
    "type": "monthly",
    "start_date": "2026-04-01",
    "end_date": "2026-04-30"
  }'

# Get planning details
curl -X GET "http://localhost:8000/api/planning/show/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"

# Update planning
curl -X PUT "http://localhost:8000/api/planning/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "title": "Updated Planning Title"
  }'

# Delete planning
curl -X DELETE "http://localhost:8000/api/planning/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"

# Archive planning
curl -X PATCH "http://localhost:8000/api/planning/archive/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"

# Unarchive planning
curl -X PATCH "http://localhost:8000/api/planning/unarchive/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"

# Create planning (legacy)
curl -X POST "http://localhost:8000/api/planning/create" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Legacy Planning"
  }'
```

---

### Planning Hours

**Base Path:** `/planninghour`  
**Auth Required:** Yes  
**Rate Limit:** 100 req/min

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| `POST` | `/planninghour` | ValidateUserTokenByBody | Create planning hour entry |
| `GET` | `/planninghour/{uuid}` | - | Get planning hour details |
| `PUT` | `/planninghour/{userUUID}` | ValidateUserTokenByRoute | Update planning hour |
| `DELETE` | `/planninghour/{uuid}` | - | Delete planning hour |

#### Examples

```bash
# Create planning hour entry
curl -X POST "http://localhost:8000/api/planninghour" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "planning_id": "{planningUUID}",
    "hours": 2.5,
    "date": "2026-04-07",
    "description": "Worked on feature X"
  }'

# Get planning hour details
curl -X GET "http://localhost:8000/api/planninghour/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"

# Update planning hour
curl -X PUT "http://localhost:8000/api/planninghour/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "hours": 3.0,
    "description": "Updated description"
  }'

# Delete planning hour
curl -X DELETE "http://localhost:8000/api/planninghour/{uuid}" \
  -H "Authorization: Bearer {sanctum_token}"
```

---

### Plans

**Base Path:** `/plans`  
**Rate Limit:** 40 req/min

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/plans/` | No | List all available plans |
| `GET` | `/plans/{uuid}` | No | Get plan details by UUID |

#### Examples

```bash
# List all available plans
curl -X GET "http://localhost:8000/api/plans/"

# Get plan details by UUID
curl -X GET "http://localhost:8000/api/plans/{uuid}"
```

---

### Subscriptions

**Base Path:** `/subscription`  
**Auth Required:** Yes  
**Rate Limit:** 100 req/min

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| `POST` | `/subscription/assign/free/{userUUID}` | ValidateUserTokenByRoute | Assign free plan to user |
| `POST` | `/subscription/assign/{userUUID}` | ValidateUserTokenByRoute | Assign plan to user |
| `PUT` | `/subscription/{userUUID}` | ValidateUserTokenByRoute | Assign plan to user (update) |
| `PATCH` | `/subscription/status/update/{subscriptionId}` | ValidateSubscriptionID | Update subscription status |
| `GET` | `/subscription/{userUUID}` | ValidateUserTokenByRoute | Get subscription details |
| `GET` | `/subscription/dashboard/{userUUID}` | ValidateUserTokenByRoute | Get subscription dashboard data |
| `PATCH` | `/subscription/{planningType}/{subscriptionId}` | ValidateSubscriptionID | Add planning usage to subscription |
| `PUT` | `/subscription/change/payment/method` | ValidateUserTokenByBodyUserID | Change payment method |
| `PUT` | `/subscription/change/subscription/plan` | ValidateUserTokenByBodyUserID | Change subscription plan |
| `DELETE` | `/subscription/cancel/{subscriptionId}` | ValidateSubscriptionID | Cancel subscription |

#### Examples

```bash
# Assign free plan to user
curl -X POST "http://localhost:8000/api/subscription/assign/free/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Assign plan to user
curl -X POST "http://localhost:8000/api/subscription/assign/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": "{planUUID}"
  }'

# Assign plan to user (update)
curl -X PUT "http://localhost:8000/api/subscription/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": "{planUUID}"
  }'

# Update subscription status
curl -X PATCH "http://localhost:8000/api/subscription/status/update/{subscriptionId}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "active"
  }'

# Get subscription details
curl -X GET "http://localhost:8000/api/subscription/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Get subscription dashboard data
curl -X GET "http://localhost:8000/api/subscription/dashboard/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Add planning usage to subscription
curl -X PATCH "http://localhost:8000/api/subscription/{planningType}/{subscriptionId}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "hours_used": 5
  }'

# Change payment method
curl -X PUT "http://localhost:8000/api/subscription/change/payment/method" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "payment_method_id": "pm_1234567890"
  }'

# Change subscription plan
curl -X PUT "http://localhost:8000/api/subscription/change/subscription/plan" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "new_plan_id": "{planUUID}"
  }'

# Cancel subscription
curl -X DELETE "http://localhost:8000/api/subscription/cancel/{subscriptionId}" \
  -H "Authorization: Bearer {sanctum_token}"
```

---

### Payment History

**Base Path:** `/payment/history`  
**Auth Required:** Yes  
**Rate Limit:** 100 req/min

| Method | Endpoint | Middleware | Description |
|--------|----------|------------|-------------|
| `GET` | `/payment/history/{userUUID}` | ValidateUserTokenByRoute | Get user payment history |
| `POST` | `/payment/history/` | ValidateUserTokenByBody | Create payment history entry |
| `PUT` | `/payment/history/{paymentId}` | ValidateUserTokenByBody | Update payment history |
| `PATCH` | `/payment/history/upload/nfe/{paymentId}` | ValidatePaymentHistoryID | Upload NFe (invoice) |
| `PATCH` | `/payment/history/status/update/{paymentId}` | ValidatePaymentHistoryID | Update payment status |

#### Examples

```bash
# Get user payment history
curl -X GET "http://localhost:8000/api/payment/history/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"

# Create payment history entry
curl -X POST "http://localhost:8000/api/payment/history/" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "{userUUID}",
    "amount": 29.90,
    "currency": "BRL",
    "status": "pending",
    "payment_method": "credit_card"
  }'

# Update payment history
curl -X PUT "http://localhost:8000/api/payment/history/{paymentId}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed",
    "notes": "Payment confirmed"
  }'

# Upload NFe (invoice)
curl -X PATCH "http://localhost:8000/api/payment/history/upload/nfe/{paymentId}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: multipart/form-data" \
  -F "nfe_file=@/path/to/invoice.pdf"

# Update payment status
curl -X PATCH "http://localhost:8000/api/payment/history/status/update/{paymentId}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paid"
  }'
```

---

### Support

| Method | Endpoint | Auth | Middleware | Rate Limit | Description |
|--------|----------|------|------------|------------|-------------|
| `POST` | `/support/email/{userUUID}` | Yes | ValidateUserTokenByRoute | 20 req/min | Create and send support email |

#### Examples

```bash
# Create and send support email
curl -X POST "http://localhost:8000/api/support/email/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Help with subscription",
    "message": "I am having trouble with my subscription plan."
  }'
```

---

### Webhooks

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/webhook/payment` | No | Stripe payment webhook handler |

#### Examples

```bash
# Stripe payment webhook (called by Stripe)
curl -X POST "http://localhost:8000/api/webhook/payment" \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: {stripe_signature}" \
  -d '{
    "id": "evt_1234567890",
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_1234567890",
        "amount": 2990,
        "currency": "brl"
      }
    }
  }'
```

---

### Authentication

| Method | Endpoint | Rate Limit | Description |
|--------|----------|------------|-------------|
| `GET` | `/token/github/{code}` | 20 req/min | Exchange GitHub code for access token |
| `GET` | `/auth/github/{token}` | 20 req/min | Authenticate via GitHub |
| `GET` | `/auth/google/{token}` | 20 req/min | Authenticate via Google |
| `DELETE` | `/logout/{userUUID}` | 10 req/min | Logout user (requires auth) |

#### Examples

```bash
# Exchange GitHub code for access token
curl -X GET "http://localhost:8000/api/token/github/{code}"

# Authenticate via GitHub
curl -X GET "http://localhost:8000/api/auth/github/{token}"

# Authenticate via Google
curl -X GET "http://localhost:8000/api/auth/google/{token}"

# Logout user
curl -X DELETE "http://localhost:8000/api/logout/{userUUID}" \
  -H "Authorization: Bearer {sanctum_token}"
```

---

## Conventions

### UUID Parameters
- `userUUID` - User unique identifier
- `uuid` - Generic resource UUID (planning, planning hour, etc.)
- `subscriptionId` - Subscription identifier
- `paymentId` - Payment history identifier

### Response Format
All endpoints follow standard Laravel JSON response conventions.

### Error Handling
- `401 Unauthorized` - Missing or invalid authentication token
- `403 Forbidden` - Valid token but insufficient permissions
- `404 Not Found` - Resource does not exist
- `422 Unprocessable Entity` - Validation errors
- `429 Too Many Requests` - Rate limit exceeded
