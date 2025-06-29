# API Endpoints Quick Reference

## Overview

Quick reference guide for all user management and authentication endpoints in the BCMS (Bestun Cars Management System).

## Authentication Endpoints

| Method | Endpoint | Description | Auth Required | Role Required |
|--------|----------|-------------|---------------|---------------|
| POST | `/bcms/auth/signin` | User sign in | No | None |
| GET | `/bcms/auth/user` | Get current user | Yes | Any |
| POST | `/bcms/auth/refresh` | Refresh access token | No | None |
| POST | `/bcms/auth/signout` | User sign out | Yes | Any |

## User Management Endpoints

| Method | Endpoint | Description | Auth Required | Role Required |
|--------|----------|-------------|---------------|---------------|
| GET | `/bcms/users` | List all users | Yes | Manager |
| POST | `/bcms/users` | Create new user | Yes | Manager |
| GET | `/bcms/users/{id}` | Show user details | Yes | Manager |
| PUT | `/bcms/users/{id}` | Update user | Yes | Manager |
| DELETE | `/bcms/users/{id}` | Delete user | Yes | Manager |

## Request/Response Examples

### Authentication

#### Sign In
```http
POST /bcms/auth/signin
Content-Type: application/json

{
  "email": "manager@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIs...",
  "refresh_token": "eyJhbGciOiJIUzI1NiIs...",
  "user": {
    "id": "uuid-here",
    "email": "manager@example.com",
    "name": "Manager Name",
    "role": "Manager"
  }
}
```

#### Get Current User
```http
GET /bcms/auth/user
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "user": {
    "id": "uuid-here",
    "email": "manager@example.com",
    "name": "Manager Name",
    "role": "Manager"
  }
}
```

### User Management

#### List Users
```http
GET /bcms/users
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": "uuid-1",
      "email": "user1@example.com",
      "name": "User One",
      "role": "User",
      "created_at": "2025-06-29T10:00:00Z",
      "updated_at": "2025-06-29T10:00:00Z"
    }
  ]
}
```

#### Create User
```http
POST /bcms/users
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "email": "newuser@example.com",
  "name": "New User",
  "role": "User",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "message": "User created successfully.",
  "user": {
    "id": "new-uuid",
    "email": "newuser@example.com",
    "name": "New User",
    "role": "User",
    "created_at": "2025-06-29T12:00:00Z",
    "updated_at": "2025-06-29T12:00:00Z"
  }
}
```

#### Update User
```http
PUT /bcms/users/{id}
Authorization: Bearer {access_token}
Content-Type: application/json

{
  "name": "Updated Name",
  "role": "Manager"
}
```

**Response (200):**
```json
{
  "message": "User updated successfully.",
  "user": {
    "id": "uuid-here",
    "email": "user@example.com",
    "name": "Updated Name",
    "role": "Manager",
    "created_at": "2025-06-29T10:00:00Z",
    "updated_at": "2025-06-29T12:30:00Z"
  }
}
```

#### Delete User
```http
DELETE /bcms/users/{id}
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "message": "User deleted successfully."
}
```

## Status Codes

| Status Code | Description | When It Occurs |
|-------------|-------------|----------------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (user creation) |
| 401 | Unauthorized | Invalid/expired token, invalid credentials |
| 403 | Forbidden | User lacks required role |
| 404 | Not Found | User not found |
| 422 | Unprocessable Entity | Validation errors, duplicate email |
| 429 | Too Many Requests | Rate limit exceeded (signin only) |
| 500 | Internal Server Error | Server-side errors |

## Error Response Format

All error responses follow this format:

```json
{
  "message": "Error description"
}
```

### Common Error Messages

- `"Unauthenticated."` - Missing or invalid token
- `"Access denied. Manager role required."` - User lacks Manager permissions
- `"User not found."` - Requested user doesn't exist
- `"This email address is already in use."` - Email uniqueness violation
- `"Validation failed."` - Request data validation errors

## Authentication Headers

For all protected endpoints, include the authorization header:

```
Authorization: Bearer {access_token}
```

## Content-Type Headers

For POST and PUT requests with JSON body:

```
Content-Type: application/json
```

## Rate Limiting

- **Sign In Endpoint:** 5 attempts per minute per IP
- **Other Endpoints:** No rate limiting currently applied

## Role Permissions Summary

### Manager Role ✅
- Full access to all user management endpoints
- Can create, read, update, delete any user
- Can list all users in the system

### User Role ❌
- Cannot access any user management endpoints
- Only has access to authentication endpoints
- All user management requests return 403 Forbidden

## Testing Endpoints

All endpoints have been tested and verified working:

- ✅ Authentication flow (signin, user info, refresh, signout)
- ✅ User listing (with proper role restrictions)
- ✅ User creation (with validation)
- ✅ User details viewing
- ✅ User updates (partial updates supported)
- ✅ User deletion (with verification)
- ✅ Role-based access control

## Integration Notes

1. **Token Management:** Store access tokens securely, use refresh tokens to maintain sessions
2. **Error Handling:** Always check status codes and handle errors appropriately
3. **Role Checking:** Verify user roles on the frontend before showing UI elements
4. **Validation:** Implement client-side validation that matches server-side rules
5. **Security:** Always use HTTPS in production, never expose tokens in logs
