# Makes API Documentation

## Overview
The Makes API provides endpoints for managing vehicle makes in the BCMS system. All endpoints require authentication via Bearer token and appropriate role-based permissions.

## Authentication
All endpoints require a valid Bearer token in the Authorization header:
```
Authorization: Bearer {access_token}
```

## Role-Based Access Control
- **Manager**: Full CRUD access (create, read, update, delete)
- **User**: Full CRUD access (create, read, update, delete)
- **Unauthenticated**: No access (401 Unauthorized)

## Base URL
All endpoints are prefixed with `/bcms/makes`

---

## Endpoints

### 1. List All Makes
Retrieve a list of all vehicle makes.

**Endpoint:** `GET /bcms/makes`

**Required Role:** Manager or User

**Request Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Response:**
- **Status Code:** 200 OK
- **Content-Type:** application/json

**Success Response Example:**
```json
[
    {
        "id": "01234567-89ab-cdef-0123-456789abcdef",
        "name": "Toyota",
        "created_at": "2025-06-29T10:00:00.000000Z",
        "updated_at": "2025-06-29T10:00:00.000000Z"
    },
    {
        "id": "01234567-89ab-cdef-0123-456789abcdef",
        "name": "Honda",
        "created_at": "2025-06-29T10:05:00.000000Z",
        "updated_at": "2025-06-29T10:05:00.000000Z"
    }
]
```

**Error Responses:**
- **401 Unauthorized:** Missing or invalid token
- **403 Forbidden:** Insufficient permissions

---

### 2. Create New Make
Create a new vehicle make.

**Endpoint:** `POST /bcms/makes`

**Required Role:** Manager or User

**Request Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "Ford"
}
```

**Validation Rules:**
- `name`: Required, string, unique

**Response:**
- **Status Code:** 201 Created
- **Content-Type:** application/json

**Success Response Example:**
```json
{
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "name": "Ford",
    "created_at": "2025-06-29T10:15:00.000000Z",
    "updated_at": "2025-06-29T10:15:00.000000Z"
}
```

**Error Responses:**
- **401 Unauthorized:** Missing or invalid token
- **403 Forbidden:** Insufficient permissions
- **422 Unprocessable Entity:** Validation errors

**Validation Error Example:**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": [
            "The name field is required."
        ]
    }
}
```

---

### 3. Get Single Make
Retrieve details of a specific vehicle make.

**Endpoint:** `GET /bcms/makes/{id}`

**Required Role:** Manager or User

**URL Parameters:**
- `id`: UUID of the make

**Request Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Response:**
- **Status Code:** 200 OK
- **Content-Type:** application/json

**Success Response Example:**
```json
{
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "name": "BMW",
    "created_at": "2025-06-29T10:20:00.000000Z",
    "updated_at": "2025-06-29T10:20:00.000000Z"
}
```

**Error Responses:**
- **401 Unauthorized:** Missing or invalid token
- **403 Forbidden:** Insufficient permissions
- **404 Not Found:** Make not found

---

### 4. Update Make
Update an existing vehicle make.

**Endpoint:** `PUT /bcms/makes/{id}`

**Required Role:** Manager or User

**URL Parameters:**
- `id`: UUID of the make

**Request Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "name": "BMW Updated"
}
```

**Validation Rules:**
- `name`: Required, string, unique

**Response:**
- **Status Code:** 200 OK
- **Content-Type:** application/json

**Success Response Example:**
```json
{
    "id": "01234567-89ab-cdef-0123-456789abcdef",
    "name": "BMW Updated",
    "created_at": "2025-06-29T10:20:00.000000Z",
    "updated_at": "2025-06-29T10:25:00.000000Z"
}
```

**Error Responses:**
- **401 Unauthorized:** Missing or invalid token
- **403 Forbidden:** Insufficient permissions
- **404 Not Found:** Make not found
- **422 Unprocessable Entity:** Validation errors

---

### 5. Delete Make
Delete a vehicle make.

**Endpoint:** `DELETE /bcms/makes/{id}`

**Required Role:** Manager or User

**URL Parameters:**
- `id`: UUID of the make

**Request Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

**Response:**
- **Status Code:** 204 No Content

**Success Response:** Empty body

**Error Responses:**
- **401 Unauthorized:** Missing or invalid token
- **403 Forbidden:** Insufficient permissions
- **404 Not Found:** Make not found

---

## Usage Examples

### Example 1: Create and List Makes
```bash
# 1. Sign in to get access token
curl -X POST http://localhost:8000/bcms/auth/signin \
  -H "Content-Type: application/json" \
  -d '{"email": "manager@example.com", "password": "password123"}'

# Response: {"access_token": "eyJ0eXAiOi...", ...}

# 2. Create a new make
curl -X POST http://localhost:8000/bcms/makes \
  -H "Authorization: Bearer eyJ0eXAiOi..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Tesla"}'

# 3. List all makes
curl -X GET http://localhost:8000/bcms/makes \
  -H "Authorization: Bearer eyJ0eXAiOi..." \
  -H "Content-Type: application/json"
```

### Example 2: Update a Make
```bash
# Update existing make
curl -X PUT http://localhost:8000/bcms/makes/01234567-89ab-cdef-0123-456789abcdef \
  -H "Authorization: Bearer eyJ0eXAiOi..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Tesla Motors"}'
```

### Example 3: Delete a Make
```bash
# Delete make
curl -X DELETE http://localhost:8000/bcms/makes/01234567-89ab-cdef-0123-456789abcdef \
  -H "Authorization: Bearer eyJ0eXAiOi..." \
  -H "Content-Type: application/json"
```

---

## Error Handling

### Common HTTP Status Codes
- **200 OK:** Successful GET/PUT request
- **201 Created:** Successful POST request
- **204 No Content:** Successful DELETE request
- **401 Unauthorized:** Missing, invalid, or expired token
- **403 Forbidden:** Valid token but insufficient permissions
- **404 Not Found:** Resource not found
- **422 Unprocessable Entity:** Validation failed

### Error Response Format
```json
{
    "message": "Error description",
    "errors": {
        "field_name": [
            "Specific error message"
        ]
    }
}
```

## Data Models

### Make Model
| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier |
| name | String | Make name (required, unique) |
| created_at | Timestamp | Creation timestamp |
| updated_at | Timestamp | Last update timestamp |

---

## Technical Implementation
- **Framework:** Laravel 11
- **Database:** Direct database operations (no external API dependencies)
- **Authentication:** Custom token-based system
- **Authorization:** Role-based middleware (`CheckRole`)
- **Validation:** Laravel Form Requests (`StoreMakeRequest`, `UpdateMakeRequest`)
- **Database Model:** Eloquent ORM with UUID primary keys
