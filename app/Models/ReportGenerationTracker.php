<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportGenerationTracker extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'report_generation_tracker';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'last_daily_report_date',
        'last_monthly_report_year',
        'last_monthly_report_month',
        'last_yearly_report_year',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'last_daily_report_date' => 'date',
        'last_monthly_report_year' => 'integer',
        'last_monthly_report_month' => 'integer',
        'last_yearly_report_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the singleton instance of the tracker
     */
    public static function getInstance(): self
    {
        // Get the first (and should be only) tracker instance
        $tracker = static::first();
        
        if (!$tracker) {
            // Create the first tracker instance
            $tracker = static::create([
                'last_daily_report_date' => null,
                'last_monthly_report_year' => null,
                'last_monthly_report_month' => null,
                'last_yearly_report_year' => null,
            ]);
        }
        
        return $tracker;
    }

    /**
     * Update the last daily report date
     */
    public function updateLastDailyReportDate(string $date): void
    {
        $this->update(['last_daily_report_date' => $date]);
    }

    /**
     * Update the last monthly report date
     */
    public function updateLastMonthlyReportDate(int $year, int $month): void
    {
        $this->update([
            'last_monthly_report_year' => $year,
            'last_monthly_report_month' => $month,
        ]);
    }

    /**
     * Update the last yearly report date
     */
    public function updateLastYearlyReportDate(int $year): void
    {
        $this->update(['last_yearly_report_year' => $year]);
    }

    /**
     * Check if we need to generate a daily report for a given date
     */
    public function needsDailyReport(string $date): bool
    {
        if (!$this->last_daily_report_date) {
            return true;
        }

        return $this->last_daily_report_date->format('Y-m-d') !== $date;
    }

    /**
     * Check if we need to generate a monthly report for given year/month
     */
    public function needsMonthlyReport(int $year, int $month): bool
    {
        if (!$this->last_monthly_report_year || !$this->last_monthly_report_month) {
            return true;
        }

        return $this->last_monthly_report_year !== $year || $this->last_monthly_report_month !== $month;
    }

    /**
     * Check if we need to generate a yearly report for given year
     */
    public function needsYearlyReport(int $year): bool
    {
        if (!$this->last_yearly_report_year) {
            return true;
        }

        return $this->last_yearly_report_year !== $year;
    }
} 