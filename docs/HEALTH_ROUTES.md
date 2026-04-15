# Health Routes

Base URL: `http://localhost:8080/api`

---

## GET /

Health check endpoint.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/` |
| **Full URL** | `http://localhost:8080/api` |
| **Authentication** | None |
| **Middleware** | `throttle:20,1` |
| **Rate Limit** | 20 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |

### Response

**Success (200 OK)**

```json
{
  "status": "healthy",
  "timestamp": "2026-04-15T10:00:00Z"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api" \
  -H "Accept: application/json"
```

---

## GET /health

Health check endpoint (alternative path).

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/health` |
| **Full URL** | `http://localhost:8080/api/health` |
| **Authentication** | None |
| **Middleware** | `throttle:20,1` |
| **Rate Limit** | 20 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |

### Response

**Success (200 OK)**

```json
{
  "status": "healthy",
  "timestamp": "2026-04-15T10:00:00Z"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/health" \
  -H "Accept: application/json"
```
