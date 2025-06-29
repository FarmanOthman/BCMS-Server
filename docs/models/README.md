# Models API Documentation

Welcome to the Models API documentation for the BCMS (Bestun Cars Management System).

## Quick Links

- **[Full API Documentation](./models-api.md)** - Complete endpoint specifications
- **[Quick Reference](./quick-reference.md)** - Fast lookup for common operations  
- **[Test Results](./test-results.md)** - Comprehensive test coverage report
- **[Technical Implementation](./technical-implementation.md)** - Architecture and implementation details

## Overview

The Models API manages car models in the BCMS system. Each model belongs to a specific make (e.g., "Camry" belongs to "Toyota") and serves as a foundation for the car inventory system.

## Key Features

✅ **Full CRUD Operations** - Create, read, update, and delete models  
✅ **Make Relationships** - Automatic loading of associated make data  
✅ **DB-Only Implementation** - No external API dependencies  
✅ **Role-Based Access** - Manager and User role support  
✅ **Data Validation** - Comprehensive validation rules  
✅ **UUID Support** - Modern UUID-based identification  

## Implementation Status

- **Controller**: ✅ Complete - Uses only Eloquent/DB
- **Model**: ✅ Complete - Proper relationships and UUIDs
- **Validation**: ✅ Complete - Form request validation
- **Tests**: ✅ Complete - 12/12 tests passing
- **Documentation**: ✅ Complete - Full API documentation

## Quick Start

### 1. Authentication
All endpoints require a Bearer token:
```bash
Authorization: Bearer <your_access_token>
```

### 2. Create a Model
```bash
curl -X POST http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{"name":"Corolla","make_id":"make-uuid-here"}'
```

### 3. Get All Models
```bash
curl -X GET http://localhost:8000/bcms/models \
  -H "Authorization: Bearer your_token"
```

## Data Structure

Each model includes:
- **Basic Info**: ID, name, timestamps
- **Relationships**: Associated make data
- **Validation**: Name unique per make, valid make reference

## Validation Rules

| Field | Requirements |
|-------|-------------|
| `name` | Required, string, max 255 chars, unique per make |
| `make_id` | Required, valid UUID, must exist in makes table |

## Permissions

Both Manager and User roles have full access to all model operations:

| Role | GET | POST | PUT | DELETE |
|------|-----|------|-----|--------|
| Manager | ✅ | ✅ | ✅ | ✅ |
| User | ✅ | ✅ | ✅ | ✅ |

## Test Coverage

**Status**: ✅ All tests passing (12/12)

- **Manager Operations**: 5 tests
- **User Operations**: 5 tests  
- **Security**: 1 test
- **Validation**: 1 test

## Related APIs

- **[Makes API](../makes/README.md)** - Manage car makes
- **[Users API](../users/README.md)** - Authentication and user management

## Support

For questions or issues:
1. Check the [Quick Reference](./quick-reference.md) for common solutions
2. Review [Test Results](./test-results.md) for expected behavior  
3. See [Technical Implementation](./technical-implementation.md) for architecture details

---

*Last updated: June 29, 2025*
