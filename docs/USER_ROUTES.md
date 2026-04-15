# User Routes

Base URL: `http://localhost:8080/api`

---

## POST /user

Create a new user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/user` |
| **Full URL** | `http://localhost:8080/api/user` |
| **Authentication** | None |
| **Middleware** | None |
| **Rate Limit** | Default |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes (for POST/PUT/PATCH) |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | User's full name |
| `email` | string | Yes | User's email address |
| `password` | string | Yes | User's password (min 8 characters) |
| `password_confirmation` | string | Yes | Password confirmation |

### Body Request Example (JSON)

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Response

**Success (201 Created)**

```json
{
  "message": "User created successfully",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
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
    "email": ["The email has already been taken."]
  }
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/user" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

---

## GET /user/{userUUID}

Get user details by UUID.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/user/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "email": "john@example.com",
  "email_verified_at": "2026-04-15T10:00:00Z",
  "github_email": null,
  "google_email": null,
  "created_at": "2026-04-15T10:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

**Unauthorized (401 Unauthorized)**

```json
{
  "message": "Unauthenticated."
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/user/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PUT /user/{userUUID}

Update user details.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PUT |
| **Path** | `/user/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | No | User's full name |
| `email` | string | No | User's email address |
| `password` | string | No | New password (min 8 characters) |
| `password_confirmation` | string | No | Password confirmation (required if password is provided) |

### Body Request Example (JSON)

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "User updated successfully",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Jane Doe",
    "email": "jane@example.com",
    "email_verified_at": "2026-04-15T10:00:00Z",
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T11:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PUT "http://localhost:8080/api/user/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com"
  }'
```

---

## DELETE /user/{userUUID}

Delete a user (soft delete).

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | DELETE |
| **Path** | `/user/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "User deleted successfully"
}
```

### Curl Example

```bash
curl -X DELETE "http://localhost:8080/api/user/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## GET /user/github/{githubEmail}

Find user by GitHub email.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/user/github/{githubEmail}` |
| **Full URL** | `http://localhost:8080/api/user/github/{githubEmail}` |
| **Authentication** | None |
| **Middleware** | None |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `githubEmail` | string | Yes | The GitHub-associated email address |

### Response

**Success (200 OK)**

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "email": "john@users.github.com",
  "github_email": "john@users.github.com",
  "google_email": null,
  "created_at": "2026-04-15T10:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

**Not Found (404 Not Found)**

```json
{
  "message": "User not found"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/user/github/john@users.github.com" \
  -H "Accept: application/json"
```

---

## GET /user/google/{googleEmail}

Find user by Google email.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/user/google/{googleEmail}` |
| **Full URL** | `http://localhost:8080/api/user/google/{googleEmail}` |
| **Authentication** | None |
| **Middleware** | None |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `googleEmail` | string | Yes | The Google-associated email address |

### Response

**Success (200 OK)**

```json
{
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "name": "John Doe",
  "email": "john@gmail.com",
  "github_email": null,
  "google_email": "john@gmail.com",
  "created_at": "2026-04-15T10:00:00Z",
  "updated_at": "2026-04-15T10:00:00Z"
}
```

**Not Found (404 Not Found)**

```json
{
  "message": "User not found"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/user/google/john@gmail.com" \
  -H "Accept: application/json"
```

---

## POST /user/resend/validationcode

Resend validation email to user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/user/resend/validationcode` |
| **Full URL** | `http://localhost:8080/api/user/resend/validationcode` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByBody` |
| **Rate Limit** | Default |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | string (UUID) | Yes | The user's unique identifier |

### Body Request Example (JSON)

```json
{
  "user_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Validation email sent successfully"
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/user/resend/validationcode" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "550e8400-e29b-41d4-a716-446655440000"
  }'
```

---

## PATCH /user/validate/{userUUID}

Validate user email.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/user/validate/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/validate/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "User validated successfully"
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/user/validate/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PATCH /user/restore/{userUUID}

Restore a soft-deleted user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/user/restore/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/restore/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Response

**Success (200 OK)**

```json
{
  "message": "User restored successfully",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@example.com",
    "deleted_at": null,
    "created_at": "2026-04-15T10:00:00Z",
    "updated_at": "2026-04-15T12:00:00Z"
  }
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/user/restore/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

---

## PATCH /user/unlink/{userUUID}

Unlink social accounts (GitHub and/or Google) from user.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | PATCH |
| **Path** | `/user/unlink/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/user/unlink/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `ValidateUserTokenByRoute` |
| **Rate Limit** | Default |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Request Body

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `provider` | string | No | Social provider to unlink (`github` or `google`). If empty, unlinks both. |

### Body Request Example (JSON)

```json
{
  "provider": "github"
}
```

### Response

**Success (200 OK)**

```json
{
  "message": "Social accounts unlinked successfully"
}
```

### Curl Example

```bash
curl -X PATCH "http://localhost:8080/api/user/unlink/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}" \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "github"
  }'
```
