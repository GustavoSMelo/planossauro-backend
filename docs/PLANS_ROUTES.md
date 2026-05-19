# Plans Routes

Base URL: `http://localhost:8080/api`

**Note:** Plans routes are publicly accessible but rate limited to 40 requests per minute.

---

## GET /plans/

List all available plans.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/plans/` |
| **Full URL** | `http://localhost:8080/api/plans/` |
| **Authentication** | None |
| **Middleware** | `throttle:40,1` |
| **Rate Limit** | 40 requests per minute |

### Response

**Success (200 OK)**

```json
{
  "data": [
    {
      "uuid": "880e8400-e29b-41d4-a716-446655440003",
      "name": "Free Plan",
      "description": "Basic free plan with limited features",
      "price": 0.00,
      "billing_period": "monthly",
      "features": [
        "Up to 3 plannings",
        "Basic support",
        "1GB storage"
      ],
      "max_plannings": 3,
      "max_planning_hours": 10,
      "is_active": true,
      "created_at": "2026-01-01T00:00:00Z",
      "updated_at": "2026-01-01T00:00:00Z"
    },
    {
      "uuid": "880e8400-e29b-41d4-a716-446655440004",
      "name": "Professional Plan",
      "description": "Professional plan with advanced features",
      "price": 49.90,
      "billing_period": "monthly",
      "features": [
        "Unlimited plannings",
        "Priority support",
        "50GB storage",
        "Advanced analytics"
      ],
      "max_plannings": -1,
      "max_planning_hours": -1,
      "is_active": true,
      "created_at": "2026-01-01T00:00:00Z",
      "updated_at": "2026-01-01T00:00:00Z"
    }
  ]
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/plans/" \
  -H "Accept: application/json"
```

---

## GET /plans/{uuid}

Get plan details by UUID.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/plans/{uuid}` |
| **Full URL** | `http://localhost:8080/api/plans/{uuid}` |
| **Authentication** | None |
| **Middleware** | `throttle:40,1` |
| **Rate Limit** | 40 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The plan's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "uuid": "880e8400-e29b-41d4-a716-446655440004",
  "name": "Professional Plan",
  "description": "Professional plan with advanced features",
  "price": 49.90,
  "billing_period": "monthly",
  "features": [
    "Unlimited plannings",
    "Priority support",
    "50GB storage",
    "Advanced analytics"
  ],
  "max_plannings": -1,
  "max_planning_hours": -1,
  "is_active": true,
  "created_at": "2026-01-01T00:00:00Z",
  "updated_at": "2026-01-01T00:00:00Z"
}
```

**Not Found (404 Not Found)**

```json
{
  "message": "Plan not found"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/plans/880e8400-e29b-41d4-a716-446655440004" \
  -H "Accept: application/json"
```
