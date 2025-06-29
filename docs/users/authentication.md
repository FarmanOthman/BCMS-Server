# Authentication API Documentation

## Overview
The authentication system provides secure user authentication with JWT tokens for the BCMS (Bestun Cars Management System). All authentication endpoints use Supabase for secure user management.

## Base URL
```
/bcms/auth
```

## Endpoints

### 1. Sign In
**POST** `/bcms/auth/signin`

Authenticates a user and returns access and refresh tokens.

#### Request Body
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

#### Response (200 OK)
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "name": "John Doe",
    "role": "Manager"
  }
}
```

#### Error Responses
- **401 Unauthorized**: Invalid credentials
- **422 Unprocessable Entity**: Validation errors
- **429 Too Many Requests**: Rate limit exceeded (5 attempts per minute)

---

### 2. Get Current User
**GET** `/bcms/auth/user`

Retrieves the authenticated user's information.

#### Headers
```
Authorization: Bearer <access_token>
```

#### Response (200 OK)
```json
{
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "name": "John Doe",
    "role": "Manager"
  }
}
```

#### Error Responses
- **401 Unauthorized**: Invalid or expired token

---

### 3. Refresh Token
**POST** `/bcms/auth/refresh`

Refreshes the access token using a valid refresh token.

#### Request Body
```json
{
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

#### Response (200 OK)
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "name": "John Doe",
    "role": "Manager"
  }
}
```

#### Error Responses
- **401 Unauthorized**: Invalid or expired refresh token

---

### 4. Sign Out
**POST** `/bcms/auth/signout`

Signs out the user and invalidates their session.

#### Headers
```
Authorization: Bearer <access_token>
```

#### Response (200 OK)
```json
{
  "message": "Successfully logged out"
}
```

#### Error Responses
- **401 Unauthorized**: Invalid or expired token

## Security Notes

1. **Rate Limiting**: Sign-in endpoint is rate limited to 5 attempts per minute
2. **Token Expiration**: Access tokens have a limited lifespan
3. **HTTPS Required**: All authentication endpoints should be used over HTTPS in production
4. **Token Storage**: Store tokens securely on the client side

## Usage Example

```javascript
// Sign in
const response = await fetch('/bcms/auth/signin', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'manager@example.com',
    password: 'password123'
  })
});

const { access_token, refresh_token, user } = await response.json();

// Use token for authenticated requests
const userResponse = await fetch('/bcms/auth/user', {
  headers: {
    'Authorization': `Bearer ${access_token}`
  }
});
```
