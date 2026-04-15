# Auth Routes

Base URL: `http://localhost:8080/api`

---

## GET /token/github/{code}

Get GitHub access token.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/token/github/{code}` |
| **Full URL** | `http://localhost:8080/api/token/github/{code}` |
| **Authentication** | None |
| **Middleware** | `throttle:20,1` |
| **Rate Limit** | 20 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `code` | string | Yes | GitHub OAuth authorization code |

### Response

**Success (200 OK)**

```json
{
  "access_token": "gho_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "token_type": "Bearer",
  "scope": "user:email"
}
```

**Bad Request (400 Bad Request)**

```json
{
  "error": "invalid_grant",
  "error_description": "The code passed is incorrect or expired."
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/token/github/abc123def456" \
  -H "Accept: application/json"
```

---

## GET /auth/github/{token}

GitHub authentication - exchange token for user session.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/auth/github/{token}` |
| **Full URL** | `http://localhost:8080/api/auth/github/{token}` |
| **Authentication** | None |
| **Middleware** | `throttle:20,1` |
| **Rate Limit** | 20 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | GitHub access token |

### Response

**Success (200 OK)**

```json
{
  "message": "Authentication successful",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@users.github.com",
    "github_email": "john@users.github.com"
  },
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Unauthorized (401 Unauthorized)**

```json
{
  "error": "Authentication failed",
  "message": "Invalid GitHub token"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/auth/github/gho_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

---

## GET /auth/google/{token}

Google authentication - exchange token for user session.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | GET |
| **Path** | `/auth/google/{token}` |
| **Full URL** | `http://localhost:8080/api/auth/google/{token}` |
| **Authentication** | None |
| **Middleware** | `throttle:20,1` |
| **Rate Limit** | 20 requests per minute |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `token` | string | Yes | Google ID token or access token |

### Response

**Success (200 OK)**

```json
{
  "message": "Authentication successful",
  "user": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "John Doe",
    "email": "john@gmail.com",
    "google_email": "john@gmail.com"
  },
  "token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Unauthorized (401 Unauthorized)**

```json
{
  "error": "Authentication failed",
  "message": "Invalid Google token"
}
```

### Curl Example

```bash
curl -X GET "http://localhost:8080/api/auth/google/ya29.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \
  -H "Accept: application/json"
```

---

## DELETE /logout/{userUUID}

Logout user and invalidate token.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | DELETE |
| **Path** | `/logout/{userUUID}` |
| **Full URL** | `http://localhost:8080/api/logout/{userUUID}` |
| **Authentication** | Sanctum (Bearer Token) |
| **Middleware** | `auth:sanctum`, `throttle:10,1` |
| **Rate Limit** | 10 requests per minute |

### URL Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userUUID` | string (UUID) | Yes | The user's unique identifier |

### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {token}` | Yes |

### Response

**Success (200 OK)**

```json
{
  "message": "Logged out successfully"
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
curl -X DELETE "http://localhost:8080/api/logout/550e8400-e29b-41d4-a716-446655440000" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {your_token_here}"
```

### Authentication Flow

#### GitHub OAuth Flow

1. Redirect user to GitHub authorization page:
   ```
   https://github.com/login/oauth/authorize?client_id={GITHUB_CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope=user:email
   ```

2. After user authorizes, GitHub redirects to your callback URL with an authorization code.

3. Exchange the code for an access token:
   ```bash
   GET /api/token/github/{code}
   ```

4. Use the access token to authenticate:
   ```bash
   GET /api/auth/github/{access_token}
   ```

5. Receive a session token to use for subsequent authenticated requests.

#### Google OAuth Flow

1. Redirect user to Google authorization page:
   ```
   https://accounts.google.com/o/oauth2/v2/auth?client_id={GOOGLE_CLIENT_ID}&redirect_uri={REDIRECT_URI}&response_type=token&scope=email%20profile
   ```

2. After user authorizes, Google redirects with an access token in the URL fragment.

3. Use the access token to authenticate:
   ```bash
   GET /api/auth/google/{access_token}
   ```

4. Receive a session token to use for subsequent authenticated requests.
