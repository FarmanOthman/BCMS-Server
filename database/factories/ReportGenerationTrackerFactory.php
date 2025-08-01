<?php

namespace Database\Factories;

use App\Models\ReportGenerationTracker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportGenerationTracker>
 */
class ReportGenerationTrackerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_daily_report_date' => Carbon::yesterday(),
            'last_monthly_report_year' => Carbon::now()->subMonth()->year,
            'last_monthly_report_month' => Carbon::now()->subMonth()->month,
            'last_yearly_report_year' => Carbon::now()->subYear()->year,
        ];
    }

    /**
     * Create a tracker with no previous reports
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_daily_report_date' => null,
            'last_monthly_report_year' => null,
            'last_monthly_report_month' => null,
            'last_yearly_report_year' => null,
        ]);
    }

    /**
     * Create a tracker with specific dates
     */
    public function withDates(string $dailyDate, int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'last_daily_report_date' => $dailyDate,
            'last_monthly_report_year' => $year,
            'last_monthly_report_month' => $month,
            'last_yearly_report_year' => $year,
        ]);
    }
} 