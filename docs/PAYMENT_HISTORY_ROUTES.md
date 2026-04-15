# Payment History Routes

Base URL: `http://localhost:8080/api`

**Note:** All payment history routes require authentication via Sanctum Bearer token and are rate limited to 100 requests per minute.

---

## GET /payment/history/{userUUID}

Get user's payment history.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/payment/history/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/payment/history/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {token}` | Yes |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `status` | string | No | Filter by status (pending, paid, failed, refunded) |

### Response

**Success (200 OK)**

```json
{
  "data": [
    {
      "uuid": "aa0e8400-e29b-41d4-a716-446655440006",
      "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "subscription_uuid": "990e8400-e29b-41d4-a716-446655440005",
      "amount": 49.90,
      "currency": "BRL",
      "status": "paid",
      "payment_method": "credit_card",
      "nfe_url": null,
      "paid_at": "2026-04-01T10:00:00Z",
      "created_at": "2026-03-25T10:00:00Z",
      "updated_at": "2026-04-01T10:00:00Z"
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 1,
  "last_page": 1
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/payment/history/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## POST /payment/history/

Create a new payment history record.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/payment/history/` |
| **Full URL** | `http://localhost:8080/api/payment/history/` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBody` |
| **Rate Limit** | 100 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {token}` | Yes |
| `Content-Type` | `application/json` | Yes (for POST/PUT/PATCH with body) |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | string (UUID) | Yes | The user's unique identifier |
| `subscription_uuid` | string (UUID) | Yes | The subscription's UUID |
| `amount` | number | Yes | Payment amount |
| `currency` | string | No | Currency code (default: "BRL") |
| `status` | string | No | Payment status (default: "pending") |
| `payment_method` | string | No | Payment method type |
| `transaction_id` | string | No | External transaction ID |

### Body Request Example (JSON)

```json
{
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "subscription_uuid": "990e8400-e29b-41d4-a716-446655440005",
  "amount": 49.90,
  "currency": "BRL",
  "status": "pending",
  "payment_method": "credit_card",
  "transaction_id": "txn_1234567890"
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "Payment history created successfully",
  "payment": {
    "uuid": "aa0e8400-e29b-41d4-a716-446655440006",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "subscription_uuid": "990e8400-e29b-41d4-a716-446655440005",
    "amount": 49.90,
    "currency": "BRL",
    "status": "pending",
    "payment_method": "credit_card",
    "transaction_id": "txn_1234567890",
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/payment/history/" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "subscription_uuid": "990e8400-e29b-41d4-a716-446655440005",
    "amount": 49.90,
    "currency": "BRL",
    "status": "pending",
    "payment_method": "credit_card",
    "transaction_id": "txn_1234567890"
  }'
```

---

## PUT /payment/history/{paymentId}

Update a payment history record.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/payment/history/{paymentId}` |
| **Full URL** | `http://localhost:8080/api/payment/history/{paymentId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBody` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentId` | string (UUID) | Yes | The payment history's unique identifier |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {token}` | Yes |
| `Content-Type` | `application/json` | Yes (for POST/PUT/PATCH with body) |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | string (UUID) | Yes | The user's unique identifier (for validation) |
| `amount` | number | No | Payment amount |
| `status` | string | No | Payment status |
| `payment_method` | string | No | Payment method type |
| `transaction_id` | string | No | External transaction ID |

### Body Request Example (JSON)

```json
{
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "amount": 49.90,
  "status": "paid",
  "payment_method": "credit_card",
  "transaction_id": "txn_1234567890"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Payment history updated successfully",
  "payment": {
    "uuid": "aa0e8400-e29b-41d4-a716-446655440006",
    "amount": 49.90,
    "status": "paid",
    "paid_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/payment/history/aa0e8400-e29b-41d4-a716-446655440006" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440000",
    "status": "paid",
    "paid_at": "2026-04-15T10:00:00Z"
  }'
```

---

## PATCH /payment/history/upload/nfe/{paymentId}

Upload NFe (Nota Fiscal Eletrônica) for a payment.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/payment/history/upload/nfe/{paymentId}` |
| **Full URL** | `http://localhost:8080/api/payment/history/upload/nfe/{paymentId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePaymentHistoryID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentId` | string (UUID) | Yes | The payment history's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `nfe_url` | string | Yes | URL to the uploaded NFe document |

### Body Request Example (JSON)

```json
{
  "nfe_url": "https://storage.example.com/nfe/aa0e8400.pdf"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "NFe uploaded successfully",
  "payment": {
    "uuid": "aa0e8400-e29b-41d4-a716-446655440006",
    "nfe_url": "https://storage.example.com/nfe/aa0e8400.pdf",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/payment/history/upload/nfe/aa0e8400-e29b-41d4-a716-446655440006" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "nfe_url": "https://storage.example.com/nfe/aa0e8400.pdf"
  }'
```

---

## PATCH /payment/history/status/update/{paymentId}

Update payment status.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/payment/history/status/update/{paymentId}` |
| **Full URL** | `http://localhost:8080/api/payment/history/status/update/{paymentId}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePaymentHistoryID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `paymentId` | string (UUID) | Yes | The payment history's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | Yes | New status (pending, paid, failed, refunded) |

### Body Request Example (JSON)

```json
{
  "status": "paid"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Payment status updated successfully",
  "payment": {
    "uuid": "aa0e8400-e29b-41d4-a716-446655440006",
    "status": "paid",
    "paid_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/payment/history/status/update/aa0e8400-e29b-41d4-a716-446655440006" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paid"
  }'
```
