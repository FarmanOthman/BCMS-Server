# Cron Job Setup for BCMS Report Generation

## Overview

The BCMS system uses Laravel's task scheduler to automatically generate sales reports. This document explains how to set up the cron jobs on your server.

## Current Schedule Configuration

The system is configured to run the following tasks automatically:

### Daily Reports
- **Command**: `reports:generate-daily`
- **Schedule**: Daily at 1:00 AM
- **Purpose**: Generates daily sales reports from sales data

### Monthly Reports
- **Command**: `reports:generate-monthly`
- **Schedule**: 1st day of every month at 2:00 AM
- **Purpose**: Generates monthly reports from daily reports + finance records

### Yearly Reports
- **Command**: `reports:generate-yearly`
- **Schedule**: January 1st at 3:00 AM
- **Purpose**: Generates yearly reports from monthly reports

## Server Setup Instructions

### 1. Add Cron Entry

Add the following cron entry to your server's crontab:

```bash
# Edit crontab
crontab -e

# Add this line (replace /path/to/your/project with actual path)
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Verify Laravel Scheduler

The Laravel scheduler is already configured in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily at 1:00 AM
    $schedule->command('reports:generate-daily')->dailyAt('01:00');

    // Monthly on 1st day at 2:00 AM
    $schedule->command('reports:generate-monthly')->monthlyOn(1, '02:00');

    // Yearly on January 1st at 3:00 AM
    $schedule->command('reports:generate-yearly')->yearlyOn(1, 1, '03:00');
}
```

### 3. Test the Setup

Test that the cron job is working:

```bash
# Test the scheduler
php artisan schedule:list

# Manually run a scheduled task
php artisan reports:generate-daily

# Check if cron is running
ps aux | grep cron
```

### 4. Logging and Monitoring

The system logs all report generation activities. Check logs at:

```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# View system cron logs (if available)
tail -f /var/log/cron
```

## Manual Commands (For Testing/Debugging)

### Generate Reports Manually

```bash
# Daily report (defaults to yesterday)
php artisan reports:generate-daily
php artisan reports:generate-daily 2024-01-15

# Monthly report (defaults to previous month)
php artisan reports:generate-monthly
php artisan reports:generate-monthly 2024 6

# Yearly report (defaults to previous year)
php artisan reports:generate-yearly
php artisan reports:generate-yearly 2023

# Test all reports for a specific date
php artisan reports:test 2024-01-15
```

### Check Report Status

```bash
# List all scheduled tasks
php artisan schedule:list

# Check if reports exist
php artisan tinker
>>> App\Models\DailySalesReport::count()
>>> App\Models\MonthlySalesReport::count()
>>> App\Models\YearlySalesReport::count()
```

## Troubleshooting

### Common Issues

1. **Cron not running**
   - Check if cron service is running: `systemctl status cron`
   - Verify crontab entry: `crontab -l`
   - Check file permissions on the project directory

2. **Reports not generating**
   - Check Laravel logs: `tail -f storage/logs/laravel.log`
   - Verify database connection
   - Check if sales data exists for the target date

3. **Permission issues**
   - Ensure web server has write permissions to storage/logs
   - Check file ownership: `chown -R www-data:www-data /path/to/project`

### Debug Commands

```bash
# Test cron execution
php artisan schedule:run --verbose

# Check command availability
php artisan list | grep reports

# Test individual commands
php artisan reports:generate-daily --help
php artisan reports:generate-monthly --help
php artisan reports:generate-yearly --help
```

## Production Considerations

### 1. Timezone Configuration

Ensure your server timezone matches your business timezone:

```bash
# Set timezone
sudo timedatectl set-timezone 'America/New_York'

# Verify timezone
date
```

### 2. Backup Strategy

Consider backing up reports before regeneration:

```bash
# Add to crontab for backup before report generation
0 0 * * * mysqldump -u username -p database_name > /backup/reports_$(date +\%Y\%m\%d).sql
```

### 3. Monitoring

Set up monitoring for report generation:

```bash
# Add to crontab to check if reports were generated
5 2 * * * php /path/to/project/artisan reports:check-status
```

## API Endpoints (Read-Only)

After removing manual creation endpoints, the available API endpoints are:

### Daily Reports
- `GET /bcms/reports/daily?date=2024-01-15` - View specific day
- `GET /bcms/reports/daily/list` - List all daily reports
- `PUT /bcms/reports/daily/2024-01-15` - Update daily report (if needed)
- `DELETE /bcms/reports/daily/2024-01-15` - Delete daily report (if needed)

### Monthly Reports
- `GET /bcms/reports/monthly?year=2024&month=6` - View specific month
- `GET /bcms/reports/monthly/list` - List all monthly reports
- `PUT /bcms/reports/monthly/2024/6` - Update monthly report (if needed)
- `DELETE /bcms/reports/monthly/2024/6` - Delete monthly report (if needed)

### Yearly Reports
- `GET /bcms/reports/yearly?year=2024` - View specific year
- `GET /bcms/reports/yearly-reports` - List all yearly reports
- `POST /bcms/reports/yearly/generate` - Generate yearly report from monthly data
- `PUT /bcms/reports/yearly/2024` - Update yearly report (if needed)
- `DELETE /bcms/reports/yearly/2024` - Delete yearly report (if needed)

## ✅ Changes Made

### Removed Manual Creation Endpoints
- ❌ `POST /bcms/reports/daily` - Manual daily report creation removed
- ❌ `POST /bcms/reports/monthly` - Manual monthly report creation removed

### Why These Changes?
- **Automation**: Reports are now generated automatically via scheduled tasks
- **Data Integrity**: Prevents manual creation of reports that might conflict with automated ones
- **Consistency**: Ensures all reports follow the same generation logic
- **Security**: Reduces potential for manual errors or data manipulation

## Security Notes

- All report endpoints require Manager role authentication
- Reports are generated automatically, reducing manual intervention
- Logs are maintained for audit trails
- Database transactions ensure data integrity 