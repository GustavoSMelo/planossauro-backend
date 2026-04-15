# Planning Hour Routes

Base URL: `http://localhost:8080/api`

**Note:** All planning hour routes require authentication via Sanctum Bearer token and are rate limited to 100 requests per minute.

---

## POST /planninghour

Create a new planning hour.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/planninghour` |
| **Full URL** | `http://localhost:8080/api/planninghour` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBody` |
| **Rate Limit** | 100 requests per minute |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `planning_uuid` | string (UUID) | Yes | The parent planning's UUID |
| `hour` | integer | Yes | Hour value (0-23) |
| `value` | number | Yes | Hour value/price |
| `user_id` | string (UUID) | Yes | Owner user ID |

### Body Request Example (JSON)

```json
{
  "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
  "hour": 10,
  "value": 150.00,
  "user_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "Planning hour created successfully",
  "planning_hour": {
    "uuid": "770e8400-e29b-41d4-a716-446655440002",
    "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
    "hour": 10,
    "value": 150.00,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

**Validation Error (422 Unprocessable Entity)**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "hour": ["The hour must be between 0 and 23."]
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/planninghour" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
    "hour": 10,
    "value": 150.00,
    "user_id": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

---

## GET /planninghour/{uuid}

Get planning hour by UUID.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/planninghour/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planninghour/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning hour's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "uuid": "770e8400-e29b-41d4-a716-446655440002",
  "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
  "hour": 10,
  "value": 150.00,
  "created_at": "2026-04-15T10:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

**Not Found (404 Not Found)**

```json
{
  "message": "Planning hour not found"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/planninghour/770e8400-e29b-41d4-a716-446655440002" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PUT /planninghour/{userUUID}

Update a planning hour.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/planninghour/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/planninghour/{userUUID}` |
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
| `uuid` | string (UUID) | Yes | The planning hour's UUID to update |
| `planning_uuid` | string (UUID) | No | The parent planning's UUID |
| `hour` | integer | No | Hour value (0-23) |
| `value` | number | No | Hour value/price |

### Body Request Example (JSON)

```json
{
  "uuid": "770e8400-e29b-41d4-a716-446655440002",
  "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
  "hour": 14,
  "value": 175.00
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Planning hour updated successfully",
  "planning_hour": {
    "uuid": "770e8400-e29b-41d4-a716-446655440002",
    "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
    "hour": 14,
    "value": 175.00,
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/planninghour/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "uuid": "770e8400-e29b-41d4-a716-446655440002",
    "planning_uuid": "660e8400-e29b-41d4-a716-446655440001",
    "hour": 14,
    "value": 175.00
  }'
```

---

## DELETE /planninghour/{uuid}

Delete a planning hour.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | DELETE |
| **Path** | `/planninghour/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planninghour/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning hour's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Planning hour deleted successfully"
}
```

### Curl Example

```bash
curl -X DELETE "http://localhost:8080/api/planninghour/770e8400-e29b-41d4-a716-446655440002" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```
