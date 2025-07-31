# User Management API Documentation

## Overview

This API provides complete CRUD (Create, Read, Update, Delete) operations for user management in the BCMS (Bestun Cars Management System). All user management endpoints require Manager role authentication.

## Base URL

```
/bcms/users
```

## Authorization

All endpoints require a valid JWT token with Manager role:

```
Authorization: Bearer <access_token>
```

## Endpoints

### 1. List All Users

**GET** `/bcms/users`

Retrieves a list of all users in the system.

#### Headers

```
Authorization: Bearer <access_token>
```

#### Response (200 OK)

```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "email": "manager@example.com",
      "name": "John Manager",
      "role": "Manager",
      "created_at": "2025-06-29T10:00:00Z",
      "updated_at": "2025-06-29T10:00:00Z"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "email": "user@example.com", 
      "name": "Jane User",
      "role": "User",
      "created_at": "2025-06-29T11:00:00Z",
      "updated_at": "2025-06-29T11:00:00Z"
    }
  ]
}
```

#### Error Responses

- **401 Unauthorized**: Invalid or expired token
- **403 Forbidden**: User does not have Manager role
- **500 Internal Server Error**: Server error

---

### 2. Create New User

**POST** `/bcms/users`

Creates a new user in the system.

#### Headers

```
Authorization: Bearer <access_token>
Content-Type: application/json
```

#### Request Body

```json
{
  "email": "newuser@example.com",
  "name": "New User",
  "role": "User",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Request Fields

- **email** (string, required): Valid email address, must be unique
- **name** (string, required): User's full name
- **role** (string, required): Either "Manager" or "User"
- **password** (string, required): Minimum 8 characters
- **password_confirmation** (string, required): Must match password

#### Response (201 Created)

```json
{
  "message": "User created successfully.",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440002",
    "email": "newuser@example.com",
    "name": "New User",
    "role": "User",
    "created_at": "2025-06-29T12:00:00Z",
    "updated_at": "2025-06-29T12:00:00Z"
  }
}
```

#### Error Responses

- **401 Unauthorized**: Invalid or expired token
- **403 Forbidden**: User does not have Manager role
- **422 Unprocessable Entity**: Validation errors or email already exists
- **500 Internal Server Error**: Server error

---

### 3. Show Single User

**GET** `/bcms/users/{id}`

Retrieves details of a specific user by their ID.

#### Parameters

- **id** (string, required): UUID of the user

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
    "role": "User",
    "created_at": "2025-06-29T10:00:00Z",
    "updated_at": "2025-06-29T10:00:00Z"
  }
}
```

#### Error Responses

- **401 Unauthorized**: Invalid or expired token
- **403 Forbidden**: User does not have Manager role
- **404 Not Found**: User not found
- **500 Internal Server Error**: Server error

---

### 4. Update User

**PUT** `/bcms/users/{id}`

Updates an existing user's information.

#### Parameters

- **id** (string, required): UUID of the user to update

#### Headers

```
Authorization: Bearer <access_token>
Content-Type: application/json
```

#### Request Body

```json
{
  "name": "Updated Name",
  "email": "updated@example.com",
  "role": "Manager",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

#### Request Fields (all optional)

- **name** (string): User's full name
- **email** (string): Valid email address, must be unique
- **role** (string): Either "Manager" or "User"
- **password** (string): New password, minimum 8 characters
- **password_confirmation** (string): Must match password field

#### Response (200 OK)

```json
{
  "message": "User updated successfully.",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "updated@example.com",
    "name": "Updated Name",
    "role": "Manager",
    "created_at": "2025-06-29T10:00:00Z",
    "updated_at": "2025-06-29T12:30:00Z"
  }
}
```

#### Error Responses

- **401 Unauthorized**: Invalid or expired token
- **403 Forbidden**: User does not have Manager role
- **404 Not Found**: User not found
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server error

---

### 5. Delete User

**DELETE** `/bcms/users/{id}`

Permanently deletes a user from the system.

#### Parameters

- **id** (string, required): UUID of the user to delete

#### Headers

```
Authorization: Bearer <access_token>
```

#### Response (200 OK)

```json
{
  "message": "User deleted successfully."
}
```

#### Error Responses

- **401 Unauthorized**: Invalid or expired token
- **403 Forbidden**: User does not have Manager role
- **404 Not Found**: User not found
- **500 Internal Server Error**: Server error

## Role-Based Access Control

### Manager Role

- Can perform all CRUD operations on users
- Can list all users
- Can create new users with any role
- Can update any user's information
- Can delete any user

### User Role

- Cannot access any user management endpoints
- All requests return **403 Forbidden**

## Usage Examples

### JavaScript/Fetch

```javascript
// List all users
const listUsers = async (token) => {
  const response = await fetch('/bcms/users', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return await response.json();
};

// Create new user
const createUser = async (token, userData) => {
  const response = await fetch('/bcms/users', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(userData)
  });
  return await response.json();
};

// Update user
const updateUser = async (token, userId, updateData) => {
  const response = await fetch(`/bcms/users/${userId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(updateData)
  });
  return await response.json();
};

// Delete user
const deleteUser = async (token, userId) => {
  const response = await fetch(`/bcms/users/${userId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  return await response.json();
};
```

### cURL Examples

```bash
# List users
curl -X GET "http://localhost:8000/bcms/users" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create user
curl -X POST "http://localhost:8000/bcms/users" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "newuser@example.com",
    "name": "New User",
    "role": "User",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Update user
curl -X PUT "http://localhost:8000/bcms/users/USER_ID" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "role": "Manager"
  }'

# Delete user
curl -X DELETE "http://localhost:8000/bcms/users/USER_ID" \
  -H "Authorization: Bearer YOUR_TOKEN"
```
