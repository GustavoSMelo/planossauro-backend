# Support Routes

Base URL: `http://localhost:8080/api`

---

## POST /support/email/{userUUID}

Create and send support email.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/support/email/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/support/email/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:20,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 20 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subject` | string | Yes | Email subject |
| `message` | string | Yes | Email message content |
| `category` | string | No | Support category (billing, technical, general) |
| `priority` | string | No | Priority level (low, medium, high, urgent) |

### Body Request Example (JSON)

```json
{
  "subject": "Billing Issue",
  "message": "I have a question about my latest invoice and need assistance.",
  "category": "billing",
  "priority": "high"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Support email sent successfully",
  "ticket": {
    "uuid": "bb0e8400-e29b-41d4-a716-446655440007",
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "subject": "Billing Issue",
    "category": "billing",
    "priority": "high",
    "status": "open",
    "created_at": "2026-04-15T10:00:00Z"
  }
}
```

**Rate Limit Exceeded (429 Too Many Requests)**

```json
{
  "message": "Too many requests. Please try again later."
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/support/email/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Billing Issue",
    "message": "I have a question about my latest invoice.",
    "category": "billing",
    "priority": "high"
  }'
```
