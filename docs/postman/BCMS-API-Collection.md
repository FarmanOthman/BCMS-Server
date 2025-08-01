# BCMS API Postman Collection

## Overview
This document provides a complete Postman collection for the BCMS (Bestun Cars Management System) API with all endpoints organized by functionality. The API includes enhanced security features where public endpoints only show available cars and hide sensitive business data, while authenticated endpoints provide full access to all information.

## Base Configuration

### Environment Variables
Create a Postman environment with these variables:

```
BASE_URL: http://localhost:8000
ACCESS_TOKEN: (will be set after authentication)
REFRESH_TOKEN: (will be set after authentication)
USER_ID: (will be set after authentication)
MANAGER_EMAIL: manager@example.com
MANAGER_PASSWORD: password123
USER_EMAIL: user@example.com
USER_PASSWORD: password123
```

### Pre-request Scripts
Add this to your collection's pre-request script to automatically set the Authorization header:

```javascript
if (pm.environment.get("ACCESS_TOKEN")) {
    pm.request.headers.add({
        key: "Authorization",
        value: "Bearer " + pm.environment.get("ACCESS_TOKEN")
    });
}
```

## Collection Structure

### 1. Authentication
- Sign In
- Refresh Token
- Sign Out
- Get Current User

### 2. User Management (Manager Only)
- List Users
- Create User
- Get User
- Update User
- Delete User

### 3. Car Management

#### Car Listing Filtering
Both public and admin car listing endpoints support the following query parameters:

**Query Parameters:**
- `limit` (optional): Number of cars per page (default: 10)
- `page` (optional): Page number (default: 1)
- `make_id` (optional): Filter by make ID
- `model_id` (optional): Filter by model ID
- `year` (optional): Filter by car year

**Example URLs:**
```
GET /bcms/cars?make_id=123&year=2020
GET /bcms/cars?model_id=456&limit=5&page=2
GET /bcms/cars?make_id=123&model_id=456&year=2020
```

**Cache Behavior:**
- **Public endpoints** (`/bcms/cars`) are cached for 60 seconds
- **Admin endpoints** (`/bcms/admin/cars`) are not cached for real-time data access
- Cache keys include all filter parameters for proper cache invalidation

#### Public Endpoints (No Authentication Required)
- List Available Cars (Public) - Only shows available cars, sold cars are hidden. Supports filtering by make_id, model_id, and year
- Get Available Car Details (Public) - Returns 404 for sold cars, hides sensitive data
- Filter Examples (Public) - Various filtering combinations for public car listings

#### Admin Endpoints (Authentication Required)
- List All Cars (Admin) - Shows all cars including sold ones with full details. Supports filtering by make_id, model_id, and year
- Get Car Details (Admin) - Shows full car details including sensitive data for all cars
- Create Car (Auth Required)
- Update Car (Auth Required)
- Delete Car (Auth Required)
- Sell Car (Complete Sales Process with Buyer Creation)

### 4. Make Management (Auth Required)
- List Makes
- Create Make
- Get Make
- Update Make
- Delete Make

### 5. Model Management (Auth Required)
- List Models
- Create Model
- Get Model
- Update Model
- Delete Model

### 6. Buyer Management (Auth Required)
- List Buyers
- Create Buyer
- Get Buyer
- Update Buyer
- Delete Buyer

### 7. Sale Management (Manager Only)
- List Sales
- Create Sale
- Get Sale
- Update Sale
- Delete Sale

### 8. Daily Sales Reports (Manager Only)
- Get Daily Report
- List Daily Reports
- Create Daily Report
- Update Daily Report
- Delete Daily Report

### 9. Monthly Sales Reports (Manager Only)
- Get Monthly Report
- List Monthly Reports
- Create Monthly Report
- Update Monthly Report
- Delete Monthly Report

### 10. Yearly Sales Reports (Manager Only)
- List Yearly Reports
- Get Yearly Report
- Create Yearly Report
- Update Yearly Report
- Delete Yearly Report
- Generate Yearly Report

### 11. Finance Records (Manager Only)
- List Finance Records
- Create Finance Record
- Get Finance Record
- Update Finance Record
- Delete Finance Record

## Detailed Endpoint Documentation

### 1. Authentication Endpoints

#### 1.1 Sign In
```
Method: POST
URL: {{BASE_URL}}/bcms/auth/signin
Headers: 
  Content-Type: application/json
Body (raw JSON):
{
    "email": "{{MANAGER_EMAIL}}",
    "password": "{{MANAGER_PASSWORD}}"
}
```

**Tests Script:**
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has access token", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.access_token).to.exist;
    pm.environment.set("ACCESS_TOKEN", jsonData.access_token);
    pm.environment.set("REFRESH_TOKEN", jsonData.refresh_token);
    pm.environment.set("USER_ID", jsonData.user.id);
});
```

#### 1.2 Refresh Token
```
Method: POST
URL: {{BASE_URL}}/bcms/auth/refresh
Headers: 
  Content-Type: application/json
Body (raw JSON):
{
    "refresh_token": "{{REFRESH_TOKEN}}"
}
```

#### 1.3 Sign Out
```
Method: POST
URL: {{BASE_URL}}/bcms/auth/signout
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 1.4 Get Current User
```
Method: GET
URL: {{BASE_URL}}/bcms/auth/user
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 2. User Management Endpoints (Manager Only)

#### 2.1 List Users
```
Method: GET
URL: {{BASE_URL}}/bcms/users
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 2.2 Create User
```
Method: POST
URL: {{BASE_URL}}/bcms/users
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "email": "newuser@example.com",
    "name": "New User",
    "role": "User",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### 2.3 Get User
```
Method: GET
URL: {{BASE_URL}}/bcms/users/{{USER_ID}}
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 2.4 Update User
```
Method: PUT
URL: {{BASE_URL}}/bcms/users/{{USER_ID}}
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "Updated User Name",
    "role": "Manager"
}
```

#### 2.5 Delete User
```
Method: DELETE
URL: {{BASE_URL}}/bcms/users/{{USER_ID}}
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 3. Car Management Endpoints

#### 3.1 List Cars (Public)
```
Method: GET
URL: {{BASE_URL}}/bcms/cars
```

#### 3.2 Get Car (Public)
```
Method: GET
URL: {{BASE_URL}}/bcms/cars/1
```

#### 3.3 Create Car (Auth Required)
```
Method: POST
URL: {{BASE_URL}}/bcms/cars
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "make_id": 1,
    "model_id": 1,
    "year": 2020,
    "color": "Red",
    "mileage": 50000,
    "price": 25000,
    "public_price": 27000,
    "status": "available",
    "description": "Well maintained car"
}
```

#### 3.4 Update Car (Auth Required)
```
Method: PUT
URL: {{BASE_URL}}/bcms/cars/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "price": 24000,
    "public_price": 26000,
    "status": "sold"
}
```

#### 3.5 Delete Car (Auth Required)
```
Method: DELETE
URL: {{BASE_URL}}/bcms/cars/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 4. Make Management Endpoints (Auth Required)

#### 4.1 List Makes
```
Method: GET
URL: {{BASE_URL}}/bcms/makes
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 4.2 Create Make
```
Method: POST
URL: {{BASE_URL}}/bcms/makes
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "Toyota",
    "description": "Japanese car manufacturer"
}
```

#### 4.3 Get Make
```
Method: GET
URL: {{BASE_URL}}/bcms/makes/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 4.4 Update Make
```
Method: PUT
URL: {{BASE_URL}}/bcms/makes/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "Toyota Motor Corporation",
    "description": "Updated description"
}
```

#### 4.5 Delete Make
```
Method: DELETE
URL: {{BASE_URL}}/bcms/makes/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 5. Model Management Endpoints (Auth Required)

#### 5.1 List Models
```
Method: GET
URL: {{BASE_URL}}/bcms/models
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 5.2 Create Model
```
Method: POST
URL: {{BASE_URL}}/bcms/models
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "make_id": 1,
    "name": "Camry",
    "description": "Sedan model"
}
```

#### 5.3 Get Model
```
Method: GET
URL: {{BASE_URL}}/bcms/models/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 5.4 Update Model
```
Method: PUT
URL: {{BASE_URL}}/bcms/models/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "Camry Hybrid",
    "description": "Updated description"
}
```

#### 5.5 Delete Model
```
Method: DELETE
URL: {{BASE_URL}}/bcms/models/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 6. Buyer Management Endpoints (Auth Required)

#### 6.1 List Buyers
```
Method: GET
URL: {{BASE_URL}}/bcms/buyers
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 6.2 Create Buyer
```
Method: POST
URL: {{BASE_URL}}/bcms/buyers
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+1234567890",
    "address": "123 Main St, City, State"
}
```

#### 6.3 Get Buyer
```
Method: GET
URL: {{BASE_URL}}/bcms/buyers/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 6.4 Update Buyer
```
Method: PUT
URL: {{BASE_URL}}/bcms/buyers/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "name": "John Smith",
    "phone": "+1234567891"
}
```

#### 6.5 Delete Buyer
```
Method: DELETE
URL: {{BASE_URL}}/bcms/buyers/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 7. Sale Management Endpoints (Manager Only)

#### 7.1 List Sales
```
Method: GET
URL: {{BASE_URL}}/bcms/sales
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 7.2 Create Sale
```
Method: POST
URL: {{BASE_URL}}/bcms/sales
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "car_id": 1,
    "buyer_id": 1,
    "sale_price": 25000,
    "sale_date": "2024-01-15",
    "payment_method": "cash"
}
```

#### 7.3 Get Sale
```
Method: GET
URL: {{BASE_URL}}/bcms/sales/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 7.4 Update Sale
```
Method: PUT
URL: {{BASE_URL}}/bcms/sales/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "sale_price": 24500,
    "payment_method": "finance"
}
```

#### 7.5 Delete Sale
```
Method: DELETE
URL: {{BASE_URL}}/bcms/sales/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 8. Daily Sales Reports (Manager Only)

#### 8.1 Get Daily Report
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/daily?date=2024-01-15
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 8.2 List Daily Reports
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/daily/list
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 8.3 Create Daily Report
```
Method: POST
URL: {{BASE_URL}}/bcms/reports/daily
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "date": "2024-01-15",
    "total_sales": 50000,
    "cars_sold": 2,
    "notes": "Good sales day"
}
```

#### 8.4 Update Daily Report
```
Method: PUT
URL: {{BASE_URL}}/bcms/reports/daily/2024-01-15
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "total_sales": 52000,
    "cars_sold": 3,
    "notes": "Updated notes"
}
```

#### 8.5 Delete Daily Report
```
Method: DELETE
URL: {{BASE_URL}}/bcms/reports/daily/2024-01-15
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 9. Monthly Sales Reports (Manager Only)

#### 9.1 Get Monthly Report
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/monthly?year=2024&month=1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 9.2 List Monthly Reports
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/monthly/list
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 9.3 Create Monthly Report
```
Method: POST
URL: {{BASE_URL}}/bcms/reports/monthly
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "year": 2024,
    "month": 1,
    "total_sales": 150000,
    "cars_sold": 6,
    "total_finance_cost": 5000
}
```

#### 9.4 Update Monthly Report
```
Method: PUT
URL: {{BASE_URL}}/bcms/reports/monthly/2024/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "total_sales": 155000,
    "cars_sold": 7
}
```

#### 9.5 Delete Monthly Report
```
Method: DELETE
URL: {{BASE_URL}}/bcms/reports/monthly/2024/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

### 10. Yearly Sales Reports (Manager Only)

#### 10.1 List Yearly Reports
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/yearly-reports
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 10.2 Get Yearly Report
```
Method: GET
URL: {{BASE_URL}}/bcms/reports/yearly?year=2024
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 10.3 Create Yearly Report
```
Method: POST
URL: {{BASE_URL}}/bcms/reports/yearly
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "year": 2024,
    "total_sales": 1800000,
    "cars_sold": 72,
    "total_finance_cost": 60000
}
```

#### 10.4 Update Yearly Report
```
Method: PUT
URL: {{BASE_URL}}/bcms/reports/yearly/2024
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "total_sales": 1850000,
    "cars_sold": 75
}
```

#### 10.5 Delete Yearly Report
```
Method: DELETE
URL: {{BASE_URL}}/bcms/reports/yearly/2024
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 10.6 Generate Yearly Report
```
Method: POST
URL: {{BASE_URL}}/bcms/reports/yearly/generate
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "year": 2024
}
```

### 11. Finance Records (Manager Only)

#### 11.1 List Finance Records
```
Method: GET
URL: {{BASE_URL}}/bcms/finance-records
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 11.2 Create Finance Record
```
Method: POST
URL: {{BASE_URL}}/bcms/finance-records
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "description": "Office rent",
    "amount": 2000,
    "type": "expense",
    "date": "2024-01-15"
}
```

#### 11.3 Get Finance Record
```
Method: GET
URL: {{BASE_URL}}/bcms/finance-records/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

#### 11.4 Update Finance Record
```
Method: PUT
URL: {{BASE_URL}}/bcms/finance-records/1
Headers: 
  Content-Type: application/json
  Authorization: Bearer {{ACCESS_TOKEN}}
Body (raw JSON):
{
    "description": "Updated office rent",
    "amount": 2200
}
```

#### 11.5 Delete Finance Record
```
Method: DELETE
URL: {{BASE_URL}}/bcms/finance-records/1
Headers: 
  Authorization: Bearer {{ACCESS_TOKEN}}
```

## Testing Workflow

### 1. Setup Authentication
1. Use the "Sign In" request with manager credentials
2. The test script will automatically set the ACCESS_TOKEN environment variable
3. All subsequent requests will use this token

### 2. Test Public Endpoints
1. Test car listing and viewing without authentication
2. Verify these work without tokens

### 3. Test Role-Based Access
1. Test manager-only endpoints with manager token
2. Test user endpoints with both manager and user tokens
3. Verify proper access control

### 4. Test Error Cases
1. Test endpoints without authentication
2. Test endpoints with invalid tokens
3. Test endpoints with insufficient permissions

## Common Test Scripts

### Authentication Test
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has required fields", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('access_token');
    pm.expect(jsonData).to.have.property('refresh_token');
    pm.expect(jsonData).to.have.property('user');
});
```

### Authorization Test
```javascript
pm.test("Status code is 403 for unauthorized access", function () {
    pm.response.to.have.status(403);
});

pm.test("Error message indicates insufficient permissions", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.message).to.include('Unauthorized');
});
```

### Data Validation Test
```javascript
pm.test("Status code is 422 for validation errors", function () {
    pm.response.to.have.status(422);
});

pm.test("Response contains validation errors", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.errors).to.exist;
});
```

## Import Instructions

1. Create a new collection in Postman
2. Import this documentation as a reference
3. Create folders for each section
4. Add requests following the patterns above
5. Set up environment variables
6. Add the pre-request script to the collection
7. Test the authentication flow first
8. Gradually test all endpoints

This collection will give you complete coverage of your BCMS API with proper authentication and authorization testing. 