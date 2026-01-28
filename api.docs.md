# Planeja.AI - API Docs - MD

This markdown file is the API documentation of Planeja.AI project

__Base URL:__ `http://localhost:8000/api`

### Headers required

```
Authorization: Bearer {token} (for auth routes)
Accept: application/json
Content-Type: application/json
```

## 👤 User routes

### 1. List user info by UUID
>
> __Auth route__

__Endpoint:__ `GET /user/{uuid}`

List all user information based on user uuid

+ Response 200 (application/json)

```json
{
    "uuid": "xxxxxx-xxxxxx-xxxxx-xxxx",
    "google_email": "email@gmail.com",
    "github_email": null,
    "cellphone_number": "99999999999",
    "full_name": "Teste usuario",
    "created_at": "2026-01-27T00:05:48.000000Z",
    "updated_at": "2026-01-27T00:15:14.000000Z",
    "github_id": null,
    "github_is_validated": 0,
    "sms_is_validated": 0,
    "google_is_validated": 0,
    "google_id": "99999999999999"
}
```

+ Response 401 (application/json)

```json
{
    "Error": "You do not have permission to see this route or informations"
}
```

---

### 2. List user info by gmail

> __Auth Route__

__Endpoint:__ `GET /user/google/{googleEmail}`

+ Response 200 (application/json)

```json
{
    "uuid": "xxxxxx-xxxxxx-xxxxx-xxxx",
    "google_email": "email@gmail.com",
    "github_email": null,
    "cellphone_number": "99999999999",
    "full_name": "Teste usuario",
    "created_at": "2026-01-27T00:05:48.000000Z",
    "updated_at": "2026-01-27T00:15:14.000000Z",
    "github_id": null,
    "github_is_validated": 0,
    "sms_is_validated": 0,
    "google_is_validated": 0,
    "google_id": "99999999999999"
}
```

---

### 3. List user info by github

> __Auth Route__

__Endpoint:__ `GET /user/github/{githubEmail}`

+ Response 200 (application/json)

```json
{
    "uuid": "xxxxxx-xxxxxx-xxxxx-xxxx",
    "google_email": null,
    "github_email": "email@gmail.com",
    "cellphone_number": "99999999999",
    "full_name": "Teste usuario",
    "created_at": "2026-01-27T00:05:48.000000Z",
    "updated_at": "2026-01-27T00:15:14.000000Z",
    "github_id": null,
    "github_is_validated": 0,
    "sms_is_validated": 0,
    "google_is_validated": 0,
    "google_id": "99999999999999"
}
```

---

### 4. Delete user by UUID

> __Auth Route__

__Endpoint:__ `DELETE /user/{uuid}`

+ Response 200 (application/json)

```json
{
    "uuid": "xxxxxx-xxxxxx-xxxxx-xxxx",
    "google_email": null,
    "github_email": "email@gmail.com",
    "cellphone_number": "99999999999",
    "full_name": "Teste usuario",
    "created_at": "2026-01-27T00:05:48.000000Z",
    "updated_at": "2026-01-27T00:15:14.000000Z",
    "github_id": null,
    "github_is_validated": 0,
    "sms_is_validated": 0,
    "google_is_validated": 0,
    "google_id": "99999999999999"
}
```

---

### 5. Create user

__Endpoint:__ `POST /user`

##### Body Params (JSON)

| Name | Typing | Opcional |
| ---- | ------ | -------- |
| full_name | String | false |
| cellphone_number | String (11 ~ 15) | false |
| google_email | String (email) | true |
| google_id | String (number) | true |
| github_email | String (email) | true |
| github_id | String (number) | true |

+ Success Response (200)

```json
{
    "message": "user created with success",
    "data": {
        "full_name": "Gustavo Santos Melo",
        "google_email": "pohelin277@naprb.com",
        "github_email": null,
        "github_id": null,
        "google_id": "1321321312",
        "cellphone_number": "119999999999999",
        "uuid": "5bb383e7-e612-4457-9495-435c516dcb2b",
        "updated_at": "2026-01-17T15:27:06.000000Z",
        "created_at": "2026-01-17T15:27:06.000000Z"
    }
}
```

### 6. Update user

> __Route auth__

__Endpoint:__ `PUT /user/{uuid}`

| Name | Typing | Opcional |
| ---- | ------ | -------- |
| full_name | String | false |
| cellphone_number | String (11 ~ 15) | false |
| google_email | String (email) | true |
| google_id | String (number) | true |
| github_email | String (email) | true |
| github_id | String (number) | true |

+ Success response (200)

```json
{
    "message": "User updated with success",
    "user": {
        "uuid": "ac024ca4-e28d-46f9-8ed5-0e32b6bb7930",
        "google_email": "gsantos15569@gmail.com",
        "github_email": null,
        "cellphone_number": "23928392832233",
        "full_name": "Teste usuario",
        "created_at": "2026-01-27T00:05:48.000000Z",
        "updated_at": "2026-01-27T00:15:14.000000Z",
        "github_id": null,
        "github_is_validated": false,
        "sms_is_validated": 0,
        "google_is_validated": false,
        "google_id": "111736191686709640333"
    }
}
```

### 7. Validate user email
This route will validate the email of user based on email sended, in this email will contain an code, if code is equal than finded in database, the user will be validated

__Endpoint:__ `PATCH /user/validate/{uuid}`

> __Auth route__

| Name | Typing |
| ---- | ------ |
| loginType | "github" or "google" |
| validationCode | String (number) |
