# Makes API Quick Reference

## Authentication
```bash
# Get access token
POST /bcms/auth/signin
{
  "email": "manager@example.com",
  "password": "password123"
}
```

## Endpoints Summary

| Method | Endpoint | Role Required | Description |
|--------|----------|---------------|-------------|
| GET | `/bcms/makes` | Manager, User | List all makes |
| POST | `/bcms/makes` | Manager, User | Create new make |
| GET | `/bcms/makes/{id}` | Manager, User | Get single make |
| PUT | `/bcms/makes/{id}` | Manager, User | Update make |
| DELETE | `/bcms/makes/{id}` | Manager, User | Delete make |

## Quick Examples

### List Makes
```bash
curl -X GET http://localhost:8000/bcms/makes \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

### Create Make
```bash
curl -X POST http://localhost:8000/bcms/makes \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"name": "Toyota"}'
```

### Update Make
```bash
curl -X PUT http://localhost:8000/bcms/makes/{id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"name": "Toyota Updated"}'
```

### Delete Make
```bash
curl -X DELETE http://localhost:8000/bcms/makes/{id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json"
```

## Response Codes
- **200 OK** - Successful GET/PUT
- **201 Created** - Successful POST
- **204 No Content** - Successful DELETE
- **401 Unauthorized** - Invalid/missing token
- **403 Forbidden** - Insufficient permissions
- **404 Not Found** - Resource not found
- **422 Unprocessable Entity** - Validation failed

## Validation Rules
- **name**: Required, string, must be unique

## Test Coverage
- ✅ 11 tests passing
- ✅ All CRUD operations
- ✅ Role-based access control
- ✅ Authentication validation
- ✅ Input validation
