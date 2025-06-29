# Models API Documentation

## Overview

The Models API provides endpoints for managing car models in the BCMS (Bestun Cars Management System). Models represent specific vehicle models that belong to a make (e.g., "Camry" belongs to "Toyota").

## Base URL

All endpoints are prefixed with `/bcms/models`

## Authentication

All endpoints require authentication via Bearer token in the Authorization header:

```
Authorization: Bearer <access_token>
```

## Data Model

### Model Object

```json
{
  "id": "string (UUID)",
  "name": "string",
  "make_id": "string (UUID)",
  "created_at": "datetime",
  "updated_at": "datetime",
  "make": {
    "id": "string (UUID)",
    "name": "string",
    "created_at": "datetime",
    "updated_at": "datetime"
  }
}
```

### Validation Rules

- **name**: Required, string, max 255 characters, must be unique within the same make
- **make_id**: Required, valid UUID, must exist in makes table

## Endpoints

### 1. Get All Models

Retrieves a list of all car models with their associated make information.

**Endpoint:** `GET /bcms/models`

**Authorization:** Required (Manager or User)

**Request Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Response:**

**Success (200 OK):**
```json
[
  {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "name": "Camry",
    "make_id": "456e7890-e89b-12d3-a456-426614174001",
    "created_at": "2025-06-29T10:00:00.000000Z",
    "updated_at": "2025-06-29T10:00:00.000000Z",
    "make": {
      "id": "456e7890-e89b-12d3-a456-426614174001",
      "name": "Toyota",
      "created_at": "2025-06-29T09:00:00.000000Z",
      "updated_at": "2025-06-29T09:00:00.000000Z"
    }
  }
]
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid authentication token

---

### 2. Create Model

Creates a new car model.

**Endpoint:** `POST /bcms/models`

**Authorization:** Required (Manager or User)

**Request Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Model S",
  "make_id": "456e7890-e89b-12d3-a456-426614174001"
}
```

**Response:**

**Success (201 Created):**
```json
{
  "id": "789e0123-e89b-12d3-a456-426614174002",
  "name": "Model S",
  "make_id": "456e7890-e89b-12d3-a456-426614174001",
  "created_at": "2025-06-29T11:00:00.000000Z",
  "updated_at": "2025-06-29T11:00:00.000000Z",
  "make": {
    "id": "456e7890-e89b-12d3-a456-426614174001",
    "name": "Tesla",
    "created_at": "2025-06-29T09:00:00.000000Z",
    "updated_at": "2025-06-29T09:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid authentication token
- `422 Unprocessable Entity`: Validation errors

**Validation Error Example:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "make_id": ["The make id field is required."]
  }
}
```

---

### 3. Get Single Model

Retrieves a specific car model by ID.

**Endpoint:** `GET /bcms/models/{id}`

**Authorization:** Required (Manager or User)

**Request Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Path Parameters:**
- `id` (string, UUID): The ID of the model to retrieve

**Response:**

**Success (200 OK):**
```json
{
  "id": "123e4567-e89b-12d3-a456-426614174000",
  "name": "Camry",
  "make_id": "456e7890-e89b-12d3-a456-426614174001",
  "created_at": "2025-06-29T10:00:00.000000Z",
  "updated_at": "2025-06-29T10:00:00.000000Z",
  "make": {
    "id": "456e7890-e89b-12d3-a456-426614174001",
    "name": "Toyota",
    "created_at": "2025-06-29T09:00:00.000000Z",
    "updated_at": "2025-06-29T09:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid authentication token
- `404 Not Found`: Model not found

---

### 4. Update Model

Updates an existing car model.

**Endpoint:** `PUT /bcms/models/{id}`

**Authorization:** Required (Manager or User)

**Request Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Path Parameters:**
- `id` (string, UUID): The ID of the model to update

**Request Body:**
```json
{
  "name": "Camry Hybrid",
  "make_id": "456e7890-e89b-12d3-a456-426614174001"
}
```

**Note:** All fields are optional for updates (using `sometimes` validation rule).

**Response:**

**Success (200 OK):**
```json
{
  "id": "123e4567-e89b-12d3-a456-426614174000",
  "name": "Camry Hybrid",
  "make_id": "456e7890-e89b-12d3-a456-426614174001",
  "created_at": "2025-06-29T10:00:00.000000Z",
  "updated_at": "2025-06-29T12:00:00.000000Z",
  "make": {
    "id": "456e7890-e89b-12d3-a456-426614174001",
    "name": "Toyota",
    "created_at": "2025-06-29T09:00:00.000000Z",
    "updated_at": "2025-06-29T09:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401 Unauthorized`: Missing or invalid authentication token
- `404 Not Found`: Model not found
- `422 Unprocessable Entity`: Validation errors

---

### 5. Delete Model

Deletes a car model.

**Endpoint:** `DELETE /bcms/models/{id}`

**Authorization:** Required (Manager or User)

**Request Headers:**
```
Authorization: Bearer <access_token>
Content-Type: application/json
```

**Path Parameters:**
- `id` (string, UUID): The ID of the model to delete

**Response:**

**Success (204 No Content):**
No response body.

**Error Responses:**
- `401 Unauthorized`: Missing or invalid authentication token
- `404 Not Found`: Model not found

## Business Rules

1. **Model Name Uniqueness**: Model names must be unique within the same make (e.g., Toyota can have a "Camry" but Honda cannot have another "Camry")
2. **Make Relationship**: Every model must belong to an existing make
3. **Cascading Considerations**: Deleting a model may affect associated cars (handled by database constraints)

## Rate Limiting

Standard API rate limiting applies. Refer to the main API documentation for details.

## Error Handling

All endpoints follow standard HTTP status codes:

- `200 OK`: Successful GET/PUT requests
- `201 Created`: Successful POST requests
- `204 No Content`: Successful DELETE requests
- `401 Unauthorized`: Authentication required or invalid
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server errors

## Examples

### Creating a Model with cURL

```bash
curl -X POST http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_access_token" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Model S",
    "make_id": "456e7890-e89b-12d3-a456-426614174001"
  }'
```

### Getting All Models with cURL

```bash
curl -X GET http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_access_token" \
  -H "Content-Type: application/json"
```

## Related Documentation

- [Makes API Documentation](../makes/makes-api.md)
- [Authentication Documentation](../users/authentication.md)
- [Models API Test Results](./test-results.md)
- [Technical Implementation](./technical-implementation.md)
