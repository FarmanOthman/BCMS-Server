# Models API Quick Reference

## Base URL
```
/bcms/models
```

## Authentication
All endpoints require Bearer token authentication.

## Endpoints Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/bcms/models` | Get all models | ✅ |
| POST | `/bcms/models` | Create new model | ✅ |
| GET | `/bcms/models/{id}` | Get specific model | ✅ |
| PUT | `/bcms/models/{id}` | Update model | ✅ |
| DELETE | `/bcms/models/{id}` | Delete model | ✅ |

## Request/Response Examples

### Create Model
```bash
POST /bcms/models
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Model S",
  "make_id": "456e7890-e89b-12d3-a456-426614174001"
}
```

### Response (201 Created)
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

## Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Successful GET/PUT |
| 201 | Created - Successful POST |
| 204 | No Content - Successful DELETE |
| 401 | Unauthorized - Invalid/missing token |
| 404 | Not Found - Model doesn't exist |
| 422 | Validation Error - Invalid data |

## Validation Rules

| Field | Rules |
|-------|-------|
| name | Required, string, max 255 chars, unique per make |
| make_id | Required, valid UUID, must exist in makes table |

## Common Errors

### Missing Authentication
```json
{
  "message": "Unauthenticated."
}
```

### Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "make_id": ["The make id field is required."]
  }
}
```

### Not Found
```json
{
  "message": "No query results for model [id]"
}
```

## cURL Examples

### Get All Models
```bash
curl -X GET http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_token"
```

### Create Model
```bash
curl -X POST http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{"name":"Corolla","make_id":"make-uuid-here"}'
```

### Update Model
```bash
curl -X PUT http://localhost:8000/bcms/models/model-id \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{"name":"Corolla Hybrid"}'
```

### Delete Model
```bash
curl -X DELETE http://localhost:8000/bcms/models/model-id \
  -H "Authorization: Bearer your_token"
```

## JavaScript/Fetch Examples

### Get All Models
```javascript
const response = await fetch('/bcms/models', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
const models = await response.json();
```

### Create Model
```javascript
const response = await fetch('/bcms/models', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Model X',
    make_id: 'make-uuid-here'
  })
});
const newModel = await response.json();
```

## Business Rules

1. **Unique Names**: Model names must be unique within the same make
2. **Make Dependency**: Models must belong to an existing make
3. **Soft Dependencies**: Deleting a model may affect associated cars

## Related APIs

- [Makes API](../makes/quick-reference.md) - Manage car makes
- [Authentication](../users/authentication.md) - Get access tokens

---

*For detailed documentation, see [models-api.md](./models-api.md)*
