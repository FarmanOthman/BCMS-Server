# Test Data Seeder Setup Guide

## Overview
This guide helps you set up test data for Postman API testing using Laravel seeders.

## Quick Setup

### Option 1: Use the Custom Command (Recommended)
```bash
# Seed test users and all test data
php artisan seed:test-data

# Seed only test users (if you already have other data)
php artisan seed:test-data --users-only
```

### Option 2: Use Laravel's Default Seeder
```bash
# Run all seeders including test data
php artisan db:seed

# Run specific seeders
php artisan db:seed --class=TestUsersSeeder
php artisan db:seed --class=TestDataSeeder
```

## What Gets Created

### Test Users
- **Manager Account:**
  - Email: `manager@example.com`
  - Password: `password123`
  - Role: Manager

- **Regular User Account:**
  - Email: `user@example.com`
  - Password: `password123`
  - Role: User

### Test Data
- **3 Makes:** Toyota, Honda, Ford
- **3 Models:** Camry, Civic, Focus
- **2 Buyers:** John Doe, Jane Smith
- **3 Cars:** 2 available, 1 sold
- **1 Sale record**
- **2 Finance records**
- **1 Daily sales report**
- **1 Monthly sales report**
- **1 Yearly sales report**

## Environment Variables Match

The seeded data matches your Postman environment variables:

| Environment Variable | Seeded Data |
|---------------------|-------------|
| `MANAGER_EMAIL` | `manager@example.com` |
| `MANAGER_PASSWORD` | `password123` |
| `USER_EMAIL` | `user@example.com` |
| `USER_PASSWORD` | `password123` |
| `TEST_CAR_ID` | `1` (Toyota Camry) |
| `TEST_MAKE_ID` | `1` (Toyota) |
| `TEST_MODEL_ID` | `1` (Camry) |
| `TEST_BUYER_ID` | `1` (John Doe) |
| `TEST_SALE_ID` | `1` (Ford Focus sale) |
| `TEST_FINANCE_RECORD_ID` | `1` (Office rent) |
| `TEST_DATE` | `2024-01-15` |
| `TEST_YEAR` | `2024` |
| `TEST_MONTH` | `1` |

## Testing Workflow

### 1. Set Up Database
```bash
# Fresh database with migrations and seeders
php artisan migrate:fresh --seed

# Or just run seeders on existing database
php artisan seed:test-data
```

### 2. Import Postman Collection
1. Import `BCMS-API-Collection.json`
2. Import `BCMS-Local-Environment.json`
3. Select "BCMS Local Environment"

### 3. Start Testing
1. Run "Sign In" with manager credentials
2. Test all endpoints with real data
3. Use the test IDs for specific resource testing

## Test Scenarios

### Authentication Testing
- Test with manager account (full access)
- Test with user account (limited access)
- Test with invalid credentials

### CRUD Operations Testing
- Create new makes, models, cars, buyers
- Read existing data (IDs 1, 2, 3 available)
- Update existing records
- Delete records (be careful with test data)

### Role-Based Access Testing
- Manager can access all endpoints
- User can access car, make, model, buyer management
- User cannot access user management, sales, reports, finance

### Report Testing
- Test daily reports with date `2024-01-15`
- Test monthly reports with year `2024`, month `1`
- Test yearly reports with year `2024`

## Troubleshooting

### "User not found" Error
```bash
# Re-run the test users seeder
php artisan seed:test-data --users-only
```

### "No data available" Error
```bash
# Re-run all test data
php artisan seed:test-data
```

### Database Connection Issues
```bash
# Check database connection
php artisan migrate:status

# Reset database if needed
php artisan migrate:fresh --seed
```

## Customization

### Add More Test Users
Edit `database/seeders/TestUsersSeeder.php`:
```php
DB::table('users')->insert([
    'id' => (string) Str::uuid(),
    'email' => 'your-email@example.com',
    'name' => 'Your Name',
    'role' => 'Manager', // or 'User'
    'password' => Hash::make('your-password'),
    'email_verified_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### Add More Test Data
Edit `database/seeders/TestDataSeeder.php` to add more cars, buyers, etc.

### Update Environment Variables
After adding new test data, update your Postman environment variables to match the new IDs.

## Production Considerations

- **Never run test seeders in production**
- **Use different credentials for production testing**
- **Backup production data before testing**
- **Use the production environment file for production testing**

## Ready to Test!

With the seeders set up, you now have:
- ✅ Test users matching environment variables
- ✅ Complete test data for all resources
- ✅ Realistic data for comprehensive testing
- ✅ Easy setup and reset commands

Start testing your BCMS API with confidence! 