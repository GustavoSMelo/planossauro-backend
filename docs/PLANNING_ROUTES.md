# Planning Routes

Base URL: `http://localhost:8080/api`

**Note:** All planning routes require authentication via Sanctum Bearer token and are rate limited to 100 requests per minute.

---

## POST /planning/search/{userUUID}

Search plannings by filters.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/planning/search/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/planning/search/{userUUID}` |
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
| `filters` | object | No | Search filters |
| `filters.name` | string | No | Planning name to search |
| `filters.status` | string | No | Planning status |
| `filters.date_from` | string (date) | No | Start date (YYYY-MM-DD) |
| `filters.date_to` | string (date) | No | End date (YYYY-MM-DD) |
| `filters.is_archived` | boolean | No | Filter archived plannings |

### Body Request Example (JSON)

```json
{
  "filters": {
    "name": "Marketing",
    "status": "active",
    "date_from": "2026-01-01",
    "date_to": "2026-12-31",
    "is_archived": false
  }
}
```

### Response

**Success (200 OK)**

```json
{
  "data": [
    {
      "uuid": "660e8400-e29b-41d4-a716-446655440001",
      "name": "Q2 Marketing Plan",
      "description": "Marketing plan for Q2 2026",
      "status": "active",
      "is_archived": false,
      "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2026-04-01T10:00:00Z",
      "updated_at": "2026-04-15T10:00:00Z"
    }
  ],
  "total": 1
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/planning/search/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "filters": {
      "name": "Marketing",
      "status": "active",
      "is_archived": false
    }
  }'
```

---

## GET /planning/paginate/{userUUID}

Get paginated plannings for a user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/planning/paginate/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/planning/paginate/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByRoute` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 15) |
| `archived` | boolean | No | Include archived plannings |

### Response

**Success (200 OK)**

```json
{
  "data": [
    {
      "uuid": "660e8400-e29b-41d4-a716-446655440001",
      "name": "Q2 Marketing Plan",
      "description": "Marketing plan for Q2 2026",
      "status": "active",
      "is_archived": false,
      "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
      "created_at": "2026-04-01T10:00:00Z",
      "updated_at": "2026-04-15T10:00:00Z"
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
curl -X GET "http://localhost:8080/api/planning/paginate/550e8400-e29b-41d4-a716-446655440000?page=1&per_page=15" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## POST /planning

Create a new planning.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/planning` |
| **Full URL** | `http://localhost:8080/api/planning` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBody` |
| **Rate Limit** | 100 requests per minute |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | Planning name |
| `description` | string | No | Planning description |
| `status` | string | No | Planning status (default: "draft") |
| `user_id` | string (UUID) | Yes | Owner user ID |
| `planning_hours` | array | No | Array of planning hour objects |

### Body Request Example (JSON)

```json
{
  "name": "Q2 Marketing Plan",
  "description": "Marketing plan for Q2 2026",
  "status": "draft",
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "planning_hours": [
    {
      "hour": 10,
      "value": 150.00
    }
  ]
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "Planning created successfully",
  "planning": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Q2 Marketing Plan",
    "description": "Marketing plan for Q2 2026",
    "status": "draft",
    "is_archived": false,
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/planning" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Q2 Marketing Plan",
    "description": "Marketing plan for Q2 2026",
    "status": "draft",
    "user_id": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

---

## GET /planning/show/{uuid}

Get planning details by UUID.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/planning/show/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planning/show/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePlanningID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "uuid": "660e8400-e29b-41d4-a716-446655440001",
  "name": "Q2 Marketing Plan",
  "description": "Marketing plan for Q2 2026",
  "status": "active",
  "is_archived": false,
  "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "planning_hours": [
    {
      "uuid": "770e8400-e29b-41d4-a716-446655440002",
      "hour": 10,
      "value": 150.00,
      "planning_uuid": "660e8400-e29b-41d4-a716-446655440001"
    }
  ],
  "created_at": "2026-04-01T10:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/planning/show/660e8400-e29b-41d4-a716-446655440001" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PUT /planning/{uuid}

Update a planning.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/planning/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planning/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidateUserTokenByBodyUserID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | No | Planning name |
| `description` | string | No | Planning description |
| `status` | string | No | Planning status |
| `user_id` | string (UUID) | Yes | Owner user ID (for validation) |

### Body Request Example (JSON)

```json
{
  "name": "Q2 Marketing Plan Updated",
  "description": "Updated description",
  "status": "active",
  "user_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Planning updated successfully",
  "planning": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "Q2 Marketing Plan Updated",
    "description": "Updated description",
    "status": "active",
    "is_archived": false,
    "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "created_at": "2026-04-01T10:00:00Z",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/planning/660e8400-e29b-41d4-a716-446655440001" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Q2 Marketing Plan Updated",
    "description": "Updated description",
    "status": "active",
    "user_id": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

---

## DELETE /planning/{uuid}

Delete a planning.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | DELETE |
| **Path** | `/planning/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planning/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePlanningID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Planning deleted successfully"
}
```

### Curl Example

```bash
curl -X DELETE "http://localhost:8080/api/planning/660e8400-e29b-41d4-a716-446655440001" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PATCH /planning/archive/{uuid}

Archive a planning.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/planning/archive/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planning/archive/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePlanningID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Planning archived successfully",
  "planning": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "is_archived": true,
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/planning/archive/660e8400-e29b-41d4-a716-446655440001" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PATCH /planning/unarchive/{uuid}

Unarchive a planning.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/planning/unarchive/{uuid}` |
| **Full URL** | `http://localhost:8080/api/planning/unarchive/{uuid}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1`, `ValidatePlanningID` |
| **Rate Limit** | 100 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `uuid` | string (UUID) | Yes | The planning's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "Planning unarchived successfully",
  "planning": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "is_archived": false,
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/planning/unarchive/660e8400-e29b-41d4-a716-446655440001" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## POST /planning/create

Create a new planning (legacy endpoint).

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/planning/create` |
| **Full URL** | `http://localhost:8080/api/planning/create` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:100,1` |
| **Rate Limit** | 100 requests per minute |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | Planning name |
| `description` | string | No | Planning description |
| `status` | string | No | Planning status (default: "draft") |

### Body Request Example (JSON)

```json
{
  "name": "New Marketing Plan",
  "description": "A new marketing plan",
  "status": "draft"
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "Planning created successfully",
  "planning": {
    "uuid": "660e8400-e29b-41d4-a716-446655440001",
    "name": "New Marketing Plan",
    "description": "A new marketing plan",
    "status": "draft",
    "is_archived": false,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T10:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/planning/create" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Marketing Plan",
    "description": "A new marketing plan",
    "status": "draft"
  }'
```
