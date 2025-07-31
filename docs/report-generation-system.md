# BCMS Report Generation System

## Overview

The BCMS (Bestun Cars Management System) includes a comprehensive report generation system that automatically creates daily, monthly, and yearly sales reports. These reports provide detailed insights into sales performance, profitability, and financial metrics.

## Report Types

### 1. Daily Sales Reports
- **Purpose**: Track daily sales performance and profitability
- **Generated**: Automatically at 1:00 AM daily via cron job
- **Manual Command**: `php artisan reports:generate-daily {date?}`

### 2. Monthly Sales Reports
- **Purpose**: Aggregate daily reports and include finance costs
- **Generated**: Automatically on the 1st day of each month at 2:00 AM
- **Manual Command**: `php artisan reports:generate-monthly {year?} {month?}`

### 3. Yearly Sales Reports
- **Purpose**: Aggregate monthly reports with year-over-year growth analysis
- **Generated**: Automatically on January 1st at 3:00 AM
- **Manual Command**: `php artisan reports:generate-yearly {year?}`

## Report Fields and Calculations

### Daily Sales Report Fields
```php
[
    'report_date' => 'date',           // Primary key - the date of the report
    'total_sales' => 'integer',        // Number of sales for the day
    'total_revenue' => 'decimal:2',    // Sum of all sale prices
    'total_profit' => 'decimal:2',     // Sum of all profit/loss amounts
    'avg_profit_per_sale' => 'decimal:2', // Average profit per sale
    'most_profitable_car_id' => 'uuid', // Car ID with highest profit
    'highest_single_profit' => 'decimal:2', // Highest single sale profit
    'created_by' => 'uuid',            // User who created the report
    'updated_by' => 'uuid',            // User who last updated the report
]
```

### Monthly Sales Report Fields
```php
[
    'year' => 'integer',               // Primary key component
    'month' => 'integer',              // Primary key component
    'start_date' => 'date',            // First day of the month
    'end_date' => 'date',              // Last day of the month
    'total_sales' => 'integer',        // Sum of daily sales
    'total_revenue' => 'decimal:2',    // Sum of daily revenue
    'total_profit' => 'decimal:2',     // Sum of daily profit (before finance)
    'avg_daily_profit' => 'decimal:2', // Average daily profit
    'best_day' => 'date',              // Day with highest profit
    'best_day_profit' => 'decimal:2',  // Profit on best day
    'profit_margin' => 'decimal:2',    // (Total Profit / Total Revenue) * 100
    'finance_cost' => 'decimal:2',     // Sum of finance records for month
    'total_finance_cost' => 'decimal:2', // Same as finance_cost
    'net_profit' => 'decimal:2',       // Total Profit - Finance Cost
    'created_by' => 'uuid',            // User who created the report
    'updated_by' => 'uuid',            // User who last updated the report
]
```

### Yearly Sales Report Fields
```php
[
    'year' => 'integer',               // Primary key - the year
    'total_sales' => 'integer',        // Sum of monthly sales
    'total_revenue' => 'decimal:2',    // Sum of monthly revenue
    'total_profit' => 'decimal:2',     // Sum of monthly profit (before finance)
    'avg_monthly_profit' => 'decimal:2', // Average monthly profit
    'best_month' => 'integer',         // Month with highest profit
    'best_month_profit' => 'decimal:2', // Profit in best month
    'profit_margin' => 'decimal:2',    // (Total Profit / Total Revenue) * 100
    'yoy_growth' => 'decimal:2',       // Year-over-year growth percentage
    'total_finance_cost' => 'decimal:2', // Sum of monthly finance costs
    'total_net_profit' => 'decimal:2', // Sum of monthly net profits
    'created_by' => 'uuid',            // User who created the report
    'updated_by' => 'uuid',            // User who last updated the report
]
```

## Commands

### Available Commands

1. **Generate Daily Report**
   ```bash
   php artisan reports:generate-daily
   php artisan reports:generate-daily 2024-01-15
   ```

2. **Generate Monthly Report**
   ```bash
   php artisan reports:generate-monthly
   php artisan reports:generate-monthly 2024 1
   ```

3. **Generate Yearly Report**
   ```bash
   php artisan reports:generate-yearly
   php artisan reports:generate-yearly 2023
   ```

4. **Test All Reports**
   ```bash
   php artisan reports:test
   php artisan reports:test 2024-01-15
   ```

5. **Update Finance Costs**
   ```bash
   php artisan reports:update-finance-costs
   ```

## Cron Job Setup

The reports are automatically generated via Laravel's task scheduler. The schedule is defined in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily sales report at 1:00 AM
    $schedule->command('reports:generate-daily')->dailyAt('01:00');
    
    // Monthly sales report on 1st day of month at 2:00 AM
    $schedule->command('reports:generate-monthly')->monthlyOn(1, '02:00');
    
    // Yearly sales report on January 1st at 3:00 AM
    $schedule->command('reports:generate-yearly')->yearlyOn(1, 1, '03:00');
}
```

### Setting Up Cron Job

To enable automatic report generation, add this cron job to your server:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## API Endpoints

### Daily Sales Reports
- `GET /api/bcms/reports/daily` - List all daily reports
- `GET /api/bcms/reports/daily/{date}` - Get specific daily report
- `PUT /api/bcms/reports/daily/{date}` - Update daily report
- `DELETE /api/bcms/reports/daily/{date}` - Delete daily report

### Monthly Sales Reports
- `GET /api/bcms/reports/monthly` - List all monthly reports
- `GET /api/bcms/reports/monthly/{year}/{month}` - Get specific monthly report
- `PUT /api/bcms/reports/monthly/{year}/{month}` - Update monthly report
- `DELETE /api/bcms/reports/monthly/{year}/{month}` - Delete monthly report

### Yearly Sales Reports
- `GET /api/bcms/reports/yearly` - List all yearly reports
- `GET /api/bcms/reports/yearly/{year}` - Get specific yearly report
- `PUT /api/bcms/reports/yearly/{year}` - Update yearly report
- `DELETE /api/bcms/reports/yearly/{year}` - Delete yearly report

## Data Sources

### Sales Data
Reports are generated from the `sales` table:
- `sale_date` - Date of the sale
- `sale_price` - Revenue from the sale
- `profit_loss` - Profit or loss from the sale
- `purchase_cost` - Cost of purchasing the car

### Finance Data
Monthly and yearly reports include finance costs from the `financerecord` table:
- `record_date` - Date of the finance record
- `cost` - Amount of the finance cost
- `type` - Type of finance record
- `category` - Category of the finance record

## Calculation Logic

### Daily Report Calculations
1. **Total Sales**: Count of sales for the specific date
2. **Total Revenue**: Sum of `sale_price` for the date
3. **Total Profit**: Sum of `profit_loss` for the date
4. **Average Profit per Sale**: Total Profit / Total Sales
5. **Most Profitable Car**: Car with highest `profit_loss`
6. **Highest Single Profit**: Highest individual `profit_loss`

### Monthly Report Calculations
1. **Aggregate Daily Data**: Sum all daily reports for the month
2. **Finance Costs**: Sum all finance records for the month
3. **Net Profit**: Total Profit - Finance Costs
4. **Profit Margin**: (Total Profit / Total Revenue) * 100
5. **Best Day**: Day with highest total profit
6. **Average Daily Profit**: Total Profit / Number of days with reports

### Yearly Report Calculations
1. **Aggregate Monthly Data**: Sum all monthly reports for the year
2. **Year-over-Year Growth**: Compare with previous year's total profit
3. **Best Month**: Month with highest total profit
4. **Average Monthly Profit**: Total Profit / Number of months with reports
5. **Total Net Profit**: Sum of all monthly net profits

## Error Handling

### Common Issues
1. **No Sales Data**: Creates empty reports with zero values
2. **Missing Finance Records**: Uses zero for finance costs
3. **Database Errors**: Logs errors and returns appropriate error codes
4. **Invalid Dates**: Validates date inputs and provides helpful error messages

### Logging
All report generation activities are logged:
- Success messages with generated metrics
- Error messages with stack traces
- Debug information for troubleshooting

## Testing

### Unit Tests
- `tests/Unit/ComprehensiveDailySalesReportTest.php`
- `tests/Unit/ComprehensiveMonthlySalesReportTest.php`
- `tests/Unit/ComprehensiveYearlySalesReportTest.php`

### Feature Tests
- `tests/Feature/Api/DailySalesReportApiTest.php`
- `tests/Feature/Api/MonthlySalesReportApiTest.php`
- `tests/Feature/Api/YearlySalesReportApiTest.php`

### Command Tests
- `tests/Feature/Commands/UpdateMonthlyFinanceCostsTest.php`

## Best Practices

1. **Data Integrity**: Always use transactions for report generation
2. **Performance**: Use database indexes on date fields
3. **Monitoring**: Set up alerts for failed report generation
4. **Backup**: Regularly backup report data
5. **Validation**: Validate all input parameters
6. **Logging**: Log all report generation activities

## Troubleshooting

### Report Not Generating
1. Check if cron job is running: `crontab -l`
2. Verify Laravel scheduler: `php artisan schedule:list`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Test manually: `php artisan reports:test`

### Incorrect Calculations
1. Verify sales data integrity
2. Check finance record dates
3. Validate report aggregation logic
4. Review calculation formulas

### Performance Issues
1. Add database indexes on date fields
2. Optimize queries with eager loading
3. Consider batch processing for large datasets
4. Monitor database performance

## Future Enhancements

1. **Real-time Reports**: WebSocket-based live updates
2. **Custom Date Ranges**: Flexible report periods
3. **Export Options**: PDF, Excel, CSV exports
4. **Advanced Analytics**: Trend analysis, forecasting
5. **Dashboard Integration**: Real-time dashboard widgets
6. **Email Notifications**: Automated report delivery
7. **Multi-tenant Support**: Separate reports per business unit 