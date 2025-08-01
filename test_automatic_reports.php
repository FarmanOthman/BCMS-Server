<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Car;
use App\Models\Buyer;
use App\Models\Sale;
use App\Models\ReportGenerationTracker;
use App\Models\DailySalesReport;
use App\Models\MonthlySalesReport;
use App\Models\YearlySalesReport;
use Illuminate\Support\Str;

echo "🧪 Testing Automatic Report Generation System\n";
echo "=============================================\n\n";

// Get test data
$car1 = Car::where('vin', 'TEST001')->first();
$car2 = Car::where('vin', 'TEST002')->first();
$car3 = Car::where('vin', 'TEST003')->first();
$buyer1 = Buyer::where('phone', '555-123-4567')->first();
$buyer2 = Buyer::where('phone', '555-234-5678')->first();
$buyer3 = Buyer::where('phone', '555-345-6789')->first();

if (!$car1 || !$car2 || !$car3 || !$buyer1 || !$buyer2 || !$buyer3) {
    echo "❌ Error: Test data not found. Please run the seeder first.\n";
    exit(1);
}

echo "📊 Initial State:\n";
echo "- Report Tracker: " . ReportGenerationTracker::count() . " records\n";
echo "- Daily Reports: " . DailySalesReport::count() . " records\n";
echo "- Monthly Reports: " . MonthlySalesReport::count() . " records\n";
echo "- Yearly Reports: " . YearlySalesReport::count() . " records\n";
echo "- Sales: " . Sale::count() . " records\n\n";

// Test 1: Sell car1 on 2025-01-08
echo "🛒 Test 1: Selling Car 1 (TEST001) on 2025-01-08\n";
echo "------------------------------------------------\n";

$sale1 = Sale::create([
    'id' => Str::uuid(),
    'car_id' => $car1->id,
    'buyer_id' => $buyer1->id,
    'sale_price' => 28000.00,
    'purchase_cost' => 25000.00,
    'profit_loss' => 3000.00,
    'sale_date' => '2025-01-08',
    'notes' => 'Test sale 1'
]);

echo "✅ Sale 1 created: Car {$car1->vin} sold to {$buyer1->name} for \${$sale1->sale_price}\n";

// Check reports after first sale
echo "\n📈 Reports after Sale 1:\n";
echo "- Report Tracker: " . ReportGenerationTracker::count() . " records\n";
echo "- Daily Reports: " . DailySalesReport::count() . " records\n";
echo "- Monthly Reports: " . MonthlySalesReport::count() . " records\n";
echo "- Yearly Reports: " . YearlySalesReport::count() . " records\n";

if (ReportGenerationTracker::count() > 0) {
    $tracker = ReportGenerationTracker::first();
    echo "- Tracker Last Daily: " . ($tracker->last_daily_report_date ?? 'None') . "\n";
    echo "- Tracker Last Monthly: " . ($tracker->last_monthly_report_year ?? 'None') . "-" . ($tracker->last_monthly_report_month ?? 'None') . "\n";
    echo "- Tracker Last Yearly: " . ($tracker->last_yearly_report_year ?? 'None') . "\n";
}

// Test 2: Sell car2 on 2025-01-08 (same day)
echo "\n🛒 Test 2: Selling Car 2 (TEST002) on 2025-01-08 (same day)\n";
echo "------------------------------------------------------------\n";

$sale2 = Sale::create([
    'id' => Str::uuid(),
    'car_id' => $car2->id,
    'buyer_id' => $buyer2->id,
    'sale_price' => 24000.00,
    'purchase_cost' => 22000.00,
    'profit_loss' => 2000.00,
    'sale_date' => '2025-01-08',
    'notes' => 'Test sale 2 - same day'
]);

echo "✅ Sale 2 created: Car {$car2->vin} sold to {$buyer2->name} for \${$sale2->sale_price}\n";

// Check reports after second sale (same day)
echo "\n📈 Reports after Sale 2 (same day):\n";
echo "- Report Tracker: " . ReportGenerationTracker::count() . " records\n";
echo "- Daily Reports: " . DailySalesReport::count() . " records\n";
echo "- Monthly Reports: " . MonthlySalesReport::count() . " records\n";
echo "- Yearly Reports: " . YearlySalesReport::count() . " records\n";

// Test 3: Sell car3 on 2025-01-09 (different day)
echo "\n🛒 Test 3: Selling Car 3 (TEST003) on 2025-01-09 (different day)\n";
echo "----------------------------------------------------------------\n";

$sale3 = Sale::create([
    'id' => Str::uuid(),
    'car_id' => $car3->id,
    'buyer_id' => $buyer3->id,
    'sale_price' => 20000.00,
    'purchase_cost' => 18000.00,
    'profit_loss' => 2000.00,
    'sale_date' => '2025-01-09',
    'notes' => 'Test sale 3 - different day'
]);

echo "✅ Sale 3 created: Car {$car3->vin} sold to {$buyer3->name} for \${$sale3->sale_price}\n";

// Check reports after third sale (different day)
echo "\n📈 Reports after Sale 3 (different day):\n";
echo "- Report Tracker: " . ReportGenerationTracker::count() . " records\n";
echo "- Daily Reports: " . DailySalesReport::count() . " records\n";
echo "- Monthly Reports: " . MonthlySalesReport::count() . " records\n";
echo "- Yearly Reports: " . YearlySalesReport::count() . " records\n";

if (ReportGenerationTracker::count() > 0) {
    $tracker = ReportGenerationTracker::first();
    echo "- Tracker Last Daily: " . ($tracker->last_daily_report_date ?? 'None') . "\n";
    echo "- Tracker Last Monthly: " . ($tracker->last_monthly_report_year ?? 'None') . "-" . ($tracker->last_monthly_report_month ?? 'None') . "\n";
    echo "- Tracker Last Yearly: " . ($tracker->last_yearly_report_year ?? 'None') . "\n";
}

// Show daily reports details
echo "\n📋 Daily Reports Details:\n";
$dailyReports = DailySalesReport::orderBy('report_date')->get();
foreach ($dailyReports as $report) {
    echo "- Date: {$report->report_date}, Sales: {$report->total_sales}, Revenue: \${$report->total_revenue}, Profit: \${$report->total_profit}\n";
}

// Show monthly reports details
echo "\n📋 Monthly Reports Details:\n";
$monthlyReports = MonthlySalesReport::orderBy('year')->orderBy('month')->get();
foreach ($monthlyReports as $report) {
    echo "- {$report->year}-{$report->month}, Sales: {$report->total_sales}, Revenue: \${$report->total_revenue}, Profit: \${$report->total_profit}\n";
}

// Show yearly reports details
echo "\n📋 Yearly Reports Details:\n";
$yearlyReports = YearlySalesReport::orderBy('year')->get();
foreach ($yearlyReports as $report) {
    echo "- {$report->year}, Sales: {$report->total_sales}, Revenue: \${$report->total_revenue}, Profit: \${$report->total_profit}\n";
}

echo "\n✅ Test completed! Check the output above to verify automatic report generation.\n"; 