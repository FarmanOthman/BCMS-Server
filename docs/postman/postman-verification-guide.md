# Postman Collection Verification Guide

## Overview

This guide helps you verify that the BCMS Postman collection is working correctly with the current API setup.

## Prerequisites

1. **Laravel Server Running**: Ensure your Laravel server is running on `http://localhost:8000`
2. **Database Seeded**: Run `php artisan db:seed --class=TestDataSeeder` to populate test data
3. **Postman Installed**: Make sure you have Postman installed and updated

## Environment Setup

### 1. Import Environment Files

1. **Import Local Environment**: 
   - File: `docs/postman/BCMS-Local-Environment.json`
   - Contains: Base URL, test user credentials, and test data IDs

2. **Import Collection**: 
   - File: `docs/postman/BCMS-API-Collection.json`
   - Contains: All 47 API endpoints organized by functionality

### 2. Environment Variables

The following variables should be automatically set during testing:

| Variable | Description | Auto-Set By |
|----------|-------------|-------------|
| `BASE_URL` | API base URL | Manual |
| `ACCESS_TOKEN` | JWT access token | Sign In request |
| `REFRESH_TOKEN` | JWT refresh token | Sign In request |
| `USER_ID` | Current user ID | Sign In request |
| `TEST_CAR_ID` | Test car UUID | Create Car request |
| `TEST_BUYER_ID` | Test buyer UUID | Create Buyer request |
| `TEST_SALE_ID` | Test sale UUID | Create Sale request |

## Testing Workflow

### Step 1: Authentication Testing

1. **Sign In (Manager)**
   - Endpoint: `POST /bcms/auth/signin`
   - Body: `{"email": "manager@example.com", "password": "password123"}`
   - Expected: 200 OK with access token
   - Auto-sets: `ACCESS_TOKEN`, `REFRESH_TOKEN`, `USER_ID`

2. **Get Current User**
   - Endpoint: `GET /bcms/auth/user`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with user data

### Step 2: Car Management Testing

1. **List Cars (Public)**
   - Endpoint: `GET /bcms/cars`
   - Expected: 200 OK with car list

2. **Create Car (Auth Required)**
   - Endpoint: `POST /bcms/cars`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Body: Includes `make_id`, `model_id`, `color`, `mileage`, `description`, etc.
   - Expected: 201 Created with car data
   - Auto-sets: `TEST_CAR_ID`

3. **Get Car**
   - Endpoint: `GET /bcms/cars/{{TEST_CAR_ID}}`
   - Expected: 200 OK with car details

4. **Update Car**
   - Endpoint: `PUT /bcms/cars/{{TEST_CAR_ID}}`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Body: Updated car data
   - Expected: 200 OK with updated car data

### Step 3: Buyer Management Testing

1. **Create Buyer (Auth Required)**
   - Endpoint: `POST /bcms/buyers`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Body: `{"name": "John Doe", "phone": "+1234567890", "car_ids": ["{{TEST_CAR_ID}}"]}`
   - Expected: 201 Created with buyer data
   - Auto-sets: `TEST_BUYER_ID`

2. **Get Buyer**
   - Endpoint: `GET /bcms/buyers/{{TEST_BUYER_ID}}`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with buyer details

3. **Update Buyer**
   - Endpoint: `PUT /bcms/buyers/{{TEST_BUYER_ID}}`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Body: Updated buyer data with `car_ids` array
   - Expected: 200 OK with updated buyer data

### Step 4: Sale Management Testing

1. **Create Sale (Manager Only)**
   - Endpoint: `POST /bcms/sales`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Body: `{"car_id": "{{TEST_CAR_ID}}", "buyer_id": "{{TEST_BUYER_ID}}", "sale_price": 25000, "sale_date": "2024-01-15", "notes": "Test sale"}`
   - Expected: 201 Created with sale data
   - Auto-sets: `TEST_SALE_ID`

2. **Get Sale**
   - Endpoint: `GET /bcms/sales/{{TEST_SALE_ID}}`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with sale details

### Step 5: Report Testing

1. **List Daily Reports**
   - Endpoint: `GET /bcms/reports/daily/list`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with daily reports list

2. **Get Daily Report**
   - Endpoint: `GET /bcms/reports/daily?date=2024-01-15`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with daily report data

3. **List Monthly Reports**
   - Endpoint: `GET /bcms/reports/monthly/list`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with monthly reports list

4. **Get Monthly Report**
   - Endpoint: `GET /bcms/reports/monthly?year=2024&month=1`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with monthly report data

5. **List Yearly Reports**
   - Endpoint: `GET /bcms/reports/yearly-reports`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with yearly reports list

6. **Get Yearly Report**
   - Endpoint: `GET /bcms/reports/yearly?year=2024`
   - Headers: `Authorization: Bearer {{ACCESS_TOKEN}}`
   - Expected: 200 OK with yearly report data

## Common Issues and Solutions

### 1. Authentication Issues

**Problem**: 401 Unauthorized errors
**Solution**: 
- Ensure you've run the Sign In request first
- Check that `ACCESS_TOKEN` is set in environment
- Verify token hasn't expired (run Refresh Token if needed)

### 2. Missing Test Data

**Problem**: 404 Not Found for test IDs
**Solution**:
- Run `php artisan db:seed --class=TestDataSeeder`
- Ensure the test users exist: `manager@example.com` and `user@example.com`

### 3. Validation Errors

**Problem**: 422 Validation errors
**Solution**:
- Check request body format matches API requirements
- Ensure required fields are provided
- Verify data types (UUIDs, dates, etc.)

### 4. Role-Based Access Issues

**Problem**: 403 Forbidden errors
**Solution**:
- Ensure you're using manager credentials for manager-only endpoints
- Check user role in database
- Verify middleware configuration

## Expected Response Formats

### Authentication Response
```json
{
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
        "id": "uuid",
        "name": "Manager User",
        "email": "manager@example.com",
        "role": "Manager"
    }
}
```

### Car Response
```json
{
    "car": {
        "id": "uuid",
        "make_id": "uuid",
        "model_id": "uuid",
        "year": 2020,
        "color": "Red",
        "mileage": 50000,
        "description": "Well maintained car",
        "cost_price": 25000,
        "public_price": 27000,
        "status": "available"
    }
}
```

### Buyer Response
```json
{
    "id": "uuid",
    "name": "John Doe",
    "phone": "+1234567890",
    "address": "123 Main St",
    "car_ids": ["uuid1", "uuid2"]
}
```

### Sale Response
```json
{
    "id": "uuid",
    "car_id": "uuid",
    "buyer_id": "uuid",
    "sale_price": 25000,
    "sale_date": "2024-01-15",
    "purchase_cost": 22000,
    "profit_loss": 3000,
    "notes": "Test sale"
}
```

## Testing Checklist

- [ ] Authentication endpoints work
- [ ] Car CRUD operations work
- [ ] Buyer CRUD operations work (with required car_ids)
- [ ] Sale CRUD operations work (manager only)
- [ ] Report endpoints work (manager only)
- [ ] Environment variables are auto-set correctly
- [ ] Error responses are handled properly
- [ ] Role-based access control works

## Troubleshooting Commands

```bash
# Check if server is running
curl http://localhost:8000/api/bcms/cars

# Check database connection
php artisan tinker
DB::table('users')->count();

# Seed test data
php artisan db:seed --class=TestDataSeeder

# Clear cache if needed
php artisan cache:clear
php artisan config:clear
```

## Notes

1. **Reports are Auto-Generated**: Daily, monthly, and yearly reports are generated automatically via cron jobs, not through POST endpoints
2. **UUIDs**: All IDs are UUIDs, not integers
3. **Required Fields**: Buyer creation requires `car_ids` array
4. **Authentication**: Use Bearer token authentication for protected endpoints
5. **Role-Based Access**: Different endpoints require different user roles 