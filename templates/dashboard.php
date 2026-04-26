<?php
$currency = $settings['currency_symbol'] ?? 'LKR';

$todayBills = ['retail_count' => 0, 'event_count' => 0, 'retail_total' => 0, 'event_total' => 0, 'count' => 0, 'total' => 0];
$monthBills = ['retail_count' => 0, 'event_count' => 0, 'retail_total' => 0, 'event_total' => 0, 'count' => 0, 'total' => 0];
$totalProducts = 0;
$lowStock = 0;
$recentBills = [];
$lowStockItems = [];
$paymentSnapshot = ['paid' => 0, 'partial' => 0, 'pending' => 0];

if ($pdo) {
    try {
        // Get today's stats
        $today = date('Y-m-d');
        $todayBillsStmt = $pdo->query("SELECT 
            SUM(CASE WHEN type = 'retail' THEN 1 ELSE 0 END) as retail_count,
            SUM(CASE WHEN type IN ('wholesale', 'event') THEN 1 ELSE 0 END) as event_count,
            COALESCE(SUM(CASE WHEN type = 'retail' THEN total_amount ELSE 0 END), 0) as retail_total,
            COALESCE(SUM(CASE WHEN type IN ('wholesale', 'event') THEN total_amount ELSE 0 END), 0) as event_total,
            SUM(CASE WHEN type IN ('retail', 'wholesale', 'event') THEN 1 ELSE 0 END) as count,
            COALESCE(SUM(CASE WHEN type IN ('retail', 'wholesale', 'event') THEN total_amount ELSE 0 END), 0) as total
            FROM bills 
            WHERE DATE(created_at) = '$today'");
        if ($todayBillsStmt) {
            $todayBills = $todayBillsStmt->fetch(PDO::FETCH_ASSOC) ?: $todayBills;
        }

        $monthBillsStmt = $pdo->query("SELECT 
            SUM(CASE WHEN type = 'retail' THEN 1 ELSE 0 END) as retail_count,
            SUM(CASE WHEN type IN ('wholesale', 'event') THEN 1 ELSE 0 END) as event_count,
            COALESCE(SUM(CASE WHEN type = 'retail' THEN total_amount ELSE 0 END), 0) as retail_total,
            COALESCE(SUM(CASE WHEN type IN ('wholesale', 'event') THEN total_amount ELSE 0 END), 0) as event_total,
            SUM(CASE WHEN type IN ('retail', 'wholesale', 'event') THEN 1 ELSE 0 END) as count,
            COALESCE(SUM(CASE WHEN type IN ('retail', 'wholesale', 'event') THEN total_amount ELSE 0 END), 0) as total
            FROM bills 
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        if ($monthBillsStmt) {
            $monthBills = $monthBillsStmt->fetch(PDO::FETCH_ASSOC) ?: $monthBills;
        }

        $totalProducts = (int) ($pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn() ?: 0);
        $lowStock = (int) ($pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level AND is_active = 1")->fetchColumn() ?: 0);

        // Recent bills
        $recentBillsStmt = $pdo->query("SELECT b.*, c.name as customer_name FROM bills b LEFT JOIN customers c ON b.customer_id = c.id ORDER BY b.created_at DESC LIMIT 5");
        if ($recentBillsStmt) {
            $recentBills = $recentBillsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        foreach ($recentBills as $bill) {
            $status = strtolower((string) ($bill['payment_status'] ?? 'pending'));
            if ($status === 'paid') {
                $paymentSnapshot['paid']++;
            } elseif ($status === 'partial') {
                $paymentSnapshot['partial']++;
            } else {
                $paymentSnapshot['pending']++;
            }
        }

        $lowStockItemsStmt = $pdo->query("SELECT sku, name, stock_quantity, min_stock_level FROM products WHERE is_active = 1 AND stock_quantity <= min_stock_level ORDER BY stock_quantity ASC, name ASC LIMIT 100");
        if ($lowStockItemsStmt) {
            $lowStockItems = $lowStockItemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (PDOException $e) {
        $_SESSION['demo_mode'] = true;
        $pdo = null;
    }
}

$todayBillCount = (int) ($todayBills['count'] ?? 0);
$monthBillCount = (int) ($monthBills['count'] ?? 0);
$todaySalesTotal = (float) ($todayBills['total'] ?? 0);
$monthSalesTotal = (float) ($monthBills['total'] ?? 0);
$todayAverageBill = $todayBillCount > 0 ? $todaySalesTotal / $todayBillCount : 0;
$monthAverageBill = $monthBillCount > 0 ? $monthSalesTotal / $monthBillCount : 0;
$monthDaysElapsed = max(1, (int) date('j'));
$monthDailyRunRate = $monthSalesTotal / $monthDaysElapsed;
$runRateDelta = $monthDailyRunRate > 0 ? (($todaySalesTotal - $monthDailyRunRate) / $monthDailyRunRate) * 100 : 0;
$retailMixPct = $monthSalesTotal > 0 ? (((float) ($monthBills['retail_total'] ?? 0)) / $monthSalesTotal) * 100 : 0;
$eventMixPct = $monthSalesTotal > 0 ? (((float) ($monthBills['event_total'] ?? 0)) / $monthSalesTotal) * 100 : 0;
$lowStockRate = $totalProducts > 0 ? (($lowStock / $totalProducts) * 100) : 0;
$collectionsRiskLabel = $paymentSnapshot['pending'] >= 2 ? 'High' : ($paymentSnapshot['pending'] >= 1 ? 'Medium' : 'Low');
$inventoryRiskLabel = $lowStockRate >= 20 ? 'High' : ($lowStockRate >= 10 ? 'Medium' : 'Low');

include 'header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted"><i class="bi bi-calendar-day me-2"></i>Today's Sales</h6>
                <h3 class="mb-0"><?= $currency ?> <?= number_format((float) ($todayBills['total'] ?? 0), 2) ?></h3>
                <small class="text-muted"><?= (int) ($todayBills['count'] ?? 0) ?> bills</small>
                <div><small class="text-muted">Retail <?= (int) ($todayBills['retail_count'] ?? 0) ?> + Event <?= (int) ($todayBills['event_count'] ?? 0) ?></small></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="?page=retail&period=today" class="btn btn-sm btn-outline-primary">Retail</a>
                    <a href="?page=event&period=today" class="btn btn-sm btn-outline-success">Events</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted"><i class="bi bi-calendar-month me-2"></i>Monthly Sales</h6>
                <h3 class="mb-0"><?= $currency ?> <?= number_format((float) ($monthBills['total'] ?? 0), 2) ?></h3>
                <small class="text-muted"><?= (int) ($monthBills['count'] ?? 0) ?> bills</small>
                <div><small class="text-muted">Retail <?= (int) ($monthBills['retail_count'] ?? 0) ?> + Event <?= (int) ($monthBills['event_count'] ?? 0) ?></small></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="?page=retail&period=month" class="btn btn-sm btn-outline-primary">Retail</a>
                    <a href="?page=event&period=month" class="btn btn-sm btn-outline-success">Events</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <h6 class="text-muted"><i class="bi bi-box-seam me-2"></i>Stocks</h6>
                <h3 class="mb-0"><?= $totalProducts ?></h3>
                <small class="text-muted">Active items</small>
                <div class="mt-2">
                    <a href="?page=products&stock_filter=active" class="btn btn-sm btn-outline-info">View More</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted"><i class="bi bi-exclamation-triangle me-2"></i>Low Stock</h6>
                <h3 class="mb-0"><?= $lowStock ?></h3>
                <small class="text-danger">Need restock</small>
                <div class="mt-2">
                    <a href="?page=products&stock_filter=low" class="btn btn-sm btn-outline-warning">View More</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-receipt me-2"></i>Recent Bills</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Bill No</th><th>Customer</th><th>Type</th><th class="text-end">Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBills as $bill): ?>
                        <?php
                            $billType = $bill['type'] ?? '';
                            $billViewPage = $billType === 'retail' ? 'retail' : 'event';
                            $billViewUrl = '?page=' . $billViewPage . '&action=view&id=' . intval($bill['id'] ?? 0);
                        ?>
                        <tr class="recent-bill-row" style="cursor:pointer" onclick="window.location.href='<?= htmlspecialchars($billViewUrl, ENT_QUOTES) ?>'">
                            <td><strong><?= htmlspecialchars($bill['bill_number']) ?></strong></td>
                            <td><?= htmlspecialchars($bill['customer_name'] ?? 'Walk-in') ?></td>
                            <td><span class="badge bg-<?= $bill['type'] == 'retail' ? 'primary' : 'success' ?>"><?= $bill['type'] == 'wholesale' ? 'Event' : 'Retail' ?></span></td>
                            <td class="text-end"><?= $currency ?> <?= number_format($bill['total_amount'], 2) ?></td>
                            <td><span class="badge bg-<?= $bill['payment_status'] == 'paid' ? 'success' : ($bill['payment_status'] == 'partial' ? 'warning' : 'danger') ?>"><?= ucfirst($bill['payment_status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentBills)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No bills yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="p-3 border-top bg-light-subtle d-flex justify-content-end">
                    <a href="?page=retail#topSellingSummaryCard" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-bar-chart-line me-2"></i>Top Selling Items Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="?page=retail&action=create" class="btn btn-primary"><i class="bi bi-cart-plus me-2"></i>New Retail Bill</a>
                    <a href="?page=event&action=create" class="btn btn-success"><i class="bi bi-calendar-event me-2"></i>New Event Bill</a>
                    <a href="?page=event#eventCalendarWidget" class="btn btn-outline-success"><i class="bi bi-calendar3 me-2"></i>View Event Calander</a>
                    <a href="?page=products&action=create" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-2"></i>Add Stock Item</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="downloadDashboardPdfReport()"><i class="bi bi-file-earmark-pdf me-2"></i>Generate Dashboard PDF</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 mb-4 border-0 shadow-sm overflow-hidden">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 bg-gradient" style="background: linear-gradient(135deg, rgba(13,110,253,.16), rgba(25,135,84,.12));">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-lightbulb-fill text-warning"></i>
                <strong>Business Insights</strong>
            </div>
            <small class="text-muted">Executive snapshot, trend interpretation, and next-step priorities</small>
        </div>
        <span class="badge rounded-pill text-bg-primary px-3 py-2">Live dashboard intelligence</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-lg-3 col-md-6">
                <div class="p-3 rounded-4 h-100 border bg-body-tertiary">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Sales Today</span>
                        <i class="bi bi-graph-up-arrow text-primary"></i>
                    </div>
                    <div class="fs-4 fw-bold"><?= $currency ?> <?= number_format($todaySalesTotal, 2) ?></div>
                    <div class="text-muted small"><?= $todayBillCount ?> bills • Avg <?= $currency ?> <?= number_format($todayAverageBill, 2) ?></div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?= max(0, min(100, 50 + $runRateDelta)) ?>%;"></div>
                    </div>
                    <div class="mt-2 small <?= $runRateDelta >= 0 ? 'text-success' : 'text-danger' ?> fw-semibold">
                        <?= $runRateDelta >= 0 ? '+' : '' ?><?= number_format($runRateDelta, 1) ?>% vs month run rate
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 rounded-4 h-100 border bg-body-tertiary">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Monthly Performance</span>
                        <i class="bi bi-calendar-month text-success"></i>
                    </div>
                    <div class="fs-4 fw-bold"><?= $currency ?> <?= number_format($monthSalesTotal, 2) ?></div>
                    <div class="text-muted small"><?= $monthBillCount ?> bills • Avg <?= $currency ?> <?= number_format($monthAverageBill, 2) ?></div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= max(0, min(100, $retailMixPct)) ?>%;"></div>
                    </div>
                    <div class="mt-2 small text-muted fw-semibold">
                        Retail <?= number_format($retailMixPct, 1) ?>% • Event <?= number_format($eventMixPct, 1) ?>%
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 rounded-4 h-100 border bg-body-tertiary">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Inventory Health</span>
                        <i class="bi bi-box-seam text-warning"></i>
                    </div>
                    <div class="fs-4 fw-bold"><?= $lowStock ?> low stock</div>
                    <div class="text-muted small"><?= $totalProducts ?> active items • <?= number_format($lowStockRate, 1) ?>% exposure</div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?= max(0, min(100, $lowStockRate)) ?>%;"></div>
                    </div>
                    <div class="mt-2 small fw-semibold <?= $inventoryRiskLabel === 'High' ? 'text-danger' : ($inventoryRiskLabel === 'Medium' ? 'text-warning' : 'text-success') ?>">
                        <?= $inventoryRiskLabel ?> restock priority
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="p-3 rounded-4 h-100 border bg-body-tertiary">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small">Collections Pulse</span>
                        <i class="bi bi-cash-coin text-danger"></i>
                    </div>
                    <div class="fs-4 fw-bold"><?= $paymentSnapshot['paid'] ?> paid</div>
                    <div class="text-muted small"><?= $paymentSnapshot['partial'] ?> partial • <?= $paymentSnapshot['pending'] ?> pending</div>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?= max(0, min(100, $paymentSnapshot['pending'] * 25)) ?>%;"></div>
                    </div>
                    <div class="mt-2 small fw-semibold <?= $collectionsRiskLabel === 'High' ? 'text-danger' : ($collectionsRiskLabel === 'Medium' ? 'text-warning' : 'text-success') ?>">
                        <?= $collectionsRiskLabel ?> follow-up priority
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="h-100 p-3 rounded-4 border bg-body-tertiary">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-bar-chart-steps text-primary"></i>
                        <strong>Business Interpretation</strong>
                    </div>
                    <div class="d-grid gap-2 text-secondary">
                        <div class="d-flex gap-2"><span class="text-primary">•</span><span>Revenue concentration this month is <?= number_format($retailMixPct, 1) ?>% retail and <?= number_format($eventMixPct, 1) ?>% event/wholesale.</span></div>
                        <div class="d-flex gap-2"><span class="text-primary">•</span><span>Average bill size today is <?= $currency ?> <?= number_format($todayAverageBill, 2) ?> compared to the month average of <?= $currency ?> <?= number_format($monthAverageBill, 2) ?>.</span></div>
                        <div class="d-flex gap-2"><span class="text-primary">•</span><span><?= $runRateDelta >= 0 ? 'Current daily pace is above the month-to-date run rate, showing healthy momentum.' : 'Current daily pace is below the month-to-date run rate, so short-term sales activity should be pushed.' ?></span></div>
                        <div class="d-flex gap-2"><span class="text-primary">•</span><span><?= $lowStock > 0 ? "Inventory pressure is present with {$lowStock} item(s) already below minimum levels." : 'No current low-stock pressure detected in active inventory.' ?></span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="h-100 p-3 rounded-4 border bg-body-tertiary">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-list-check text-success"></i>
                        <strong>Recommended Actions</strong>
                    </div>
                    <div class="d-grid gap-3">
                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge text-bg-danger rounded-pill mt-1">High</span>
                            <div>
                                <div class="fw-semibold">Follow up pending bills</div>
                                <div class="text-muted small"><?= $collectionsRiskLabel === 'High' ? 'Run payment follow-ups daily to speed up cash conversion.' : 'Keep reminders active for partial and pending bills.' ?></div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge text-bg-warning rounded-pill mt-1">High</span>
                            <div>
                                <div class="fw-semibold">Replenish low-stock items</div>
                                <div class="text-muted small"><?= $inventoryRiskLabel === 'High' ? 'Create urgent replenishment orders for shortage items.' : 'Review reorder points and restock the items already near minimum level.' ?></div>
                            </div>
                        </div>
                        <div class="d-flex gap-3 align-items-start">
                            <span class="badge text-bg-primary rounded-pill mt-1">Medium</span>
                            <div>
                                <div class="fw-semibold">Push targeted sales</div>
                                <div class="text-muted small"><?= $runRateDelta < 0 ? 'Use bundles, upsell scripts, and repeat-buyer calls to lift today’s pace.' : 'Sustain current momentum with targeted upsells on high-margin items.' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>
<script>
const dashboardReportData = {
    companyName: <?= json_encode($settings['company_name'] ?? 'Sri Ram Fire Works') ?>,
    currency: <?= json_encode($currency) ?>,
    todaySales: {
        total: <?= json_encode((float) ($todayBills['total'] ?? 0)) ?>,
        count: <?= json_encode((int) ($todayBills['count'] ?? 0)) ?>,
        retailTotal: <?= json_encode((float) ($todayBills['retail_total'] ?? 0)) ?>,
        eventTotal: <?= json_encode((float) ($todayBills['event_total'] ?? 0)) ?>,
        retailCount: <?= json_encode((int) ($todayBills['retail_count'] ?? 0)) ?>,
        eventCount: <?= json_encode((int) ($todayBills['event_count'] ?? 0)) ?>
    },
    monthlySales: {
        total: <?= json_encode((float) ($monthBills['total'] ?? 0)) ?>,
        count: <?= json_encode((int) ($monthBills['count'] ?? 0)) ?>,
        retailTotal: <?= json_encode((float) ($monthBills['retail_total'] ?? 0)) ?>,
        eventTotal: <?= json_encode((float) ($monthBills['event_total'] ?? 0)) ?>,
        retailCount: <?= json_encode((int) ($monthBills['retail_count'] ?? 0)) ?>,
        eventCount: <?= json_encode((int) ($monthBills['event_count'] ?? 0)) ?>
    },
    stocks: {
        activeItems: <?= json_encode((int) $totalProducts) ?>,
        lowStockItems: <?= json_encode((int) $lowStock) ?>
    },
    recentBills: <?= json_encode(array_map(function ($bill) {
        return [
            'bill_number' => $bill['bill_number'] ?? '',
            'created_at' => $bill['created_at'] ?? '',
            'customer_name' => $bill['customer_name'] ?? 'Walk-in',
            'type' => $bill['type'] ?? '',
            'total_amount' => floatval($bill['total_amount'] ?? 0),
            'payment_status' => $bill['payment_status'] ?? 'pending'
        ];
    }, $recentBills), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    lowStockDetails: <?= json_encode(array_map(function ($item) {
        return [
            'sku' => $item['sku'] ?? '',
            'name' => $item['name'] ?? '',
            'stock_quantity' => intval($item['stock_quantity'] ?? 0),
            'min_stock_level' => intval($item['min_stock_level'] ?? 0)
        ];
    }, $lowStockItems), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    generatedAt: <?= json_encode(date('Y-m-d H:i:s')) ?>
};

function downloadDashboardPdfReport() {
    if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
        alert('PDF library failed to load. Please refresh and try again.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');

    const fmtMoney = (value) => `${dashboardReportData.currency} ${Number(value || 0).toFixed(2)}`;
    const toPercent = (value) => `${Number(value || 0).toFixed(1)}%`;
    const safeDivide = (num, den) => den > 0 ? num / den : 0;

    const todayAvgBill = safeDivide(dashboardReportData.todaySales.total, dashboardReportData.todaySales.count);
    const monthAvgBill = safeDivide(dashboardReportData.monthlySales.total, dashboardReportData.monthlySales.count);
    const monthDaysElapsed = new Date().getDate();
    const monthDailyRunRate = safeDivide(dashboardReportData.monthlySales.total, monthDaysElapsed);
    const runRateDelta = monthDailyRunRate > 0
        ? ((dashboardReportData.todaySales.total - monthDailyRunRate) / monthDailyRunRate) * 100
        : 0;

    const retailMixPct = safeDivide(dashboardReportData.monthlySales.retailTotal, dashboardReportData.monthlySales.total) * 100;
    const eventMixPct = safeDivide(dashboardReportData.monthlySales.eventTotal, dashboardReportData.monthlySales.total) * 100;
    const lowStockRate = safeDivide(dashboardReportData.stocks.lowStockItems, dashboardReportData.stocks.activeItems) * 100;

    const paymentSnapshot = dashboardReportData.recentBills.reduce((acc, bill) => {
        const status = String(bill.payment_status || 'pending').toLowerCase();
        if (status === 'paid') acc.paid += 1;
        else if (status === 'partial') acc.partial += 1;
        else acc.pending += 1;
        return acc;
    }, { paid: 0, partial: 0, pending: 0 });

    const collectionsRiskLabel = paymentSnapshot.pending >= 2 ? 'High' : (paymentSnapshot.pending >= 1 ? 'Medium' : 'Low');
    const inventoryRiskLabel = lowStockRate >= 20 ? 'High' : (lowStockRate >= 10 ? 'Medium' : 'Low');

    const sectionTitle = (text, y) => {
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(25, 47, 89);
        doc.text(text, 40, y);
        doc.setDrawColor(210, 220, 235);
        doc.line(40, y + 6, 555, y + 6);
    };

    const writeParagraph = (text, y, maxWidth = 515) => {
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(55, 65, 81);
        const lines = doc.splitTextToSize(text, maxWidth);
        doc.text(lines, 40, y);
        return y + (lines.length * 13);
    };

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.setTextColor(17, 24, 39);
    doc.text(`${dashboardReportData.companyName} - Business Insights Report`, 40, 40);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(75, 85, 99);
    doc.text(`Generated: ${dashboardReportData.generatedAt}`, 40, 58);

    sectionTitle('Executive Snapshot', 88);
    doc.autoTable({
        startY: 100,
        head: [['Insight Area', 'Current Position', 'Signal']],
        body: [
            ['Sales Today', `${fmtMoney(dashboardReportData.todaySales.total)} across ${dashboardReportData.todaySales.count} bills`, runRateDelta >= 0 ? `Above monthly run rate by ${toPercent(runRateDelta)}` : `Below monthly run rate by ${toPercent(Math.abs(runRateDelta))}`],
            ['Monthly Performance', `${fmtMoney(dashboardReportData.monthlySales.total)} across ${dashboardReportData.monthlySales.count} bills`, `Average bill value: ${fmtMoney(monthAvgBill)}`],
            ['Inventory Health', `${dashboardReportData.stocks.lowStockItems} low stock out of ${dashboardReportData.stocks.activeItems} active items`, `${toPercent(lowStockRate)} low stock exposure (${inventoryRiskLabel} risk)`],
            ['Collections Pulse', `${paymentSnapshot.paid} paid, ${paymentSnapshot.partial} partial, ${paymentSnapshot.pending} pending (recent bills)`, `${collectionsRiskLabel} collections follow-up priority`]
        ],
        theme: 'grid',
        styles: { fontSize: 9, cellPadding: 6 },
        headStyles: { fillColor: [30, 64, 175] },
        columnStyles: {
            0: { cellWidth: 130 },
            1: { cellWidth: 200 },
            2: { cellWidth: 185 }
        }
    });

    let cursorY = doc.lastAutoTable.finalY + 24;
    sectionTitle('Business Interpretation', cursorY);
    cursorY += 16;

    const interpretationLines = [
        `Revenue concentration this month is ${toPercent(retailMixPct)} retail and ${toPercent(eventMixPct)} event/wholesale.`,
        `Average bill size today is ${fmtMoney(todayAvgBill)} compared to monthly average ${fmtMoney(monthAvgBill)}.`,
        runRateDelta >= 0
            ? 'Current daily pace is trending above the month-to-date run rate, indicating healthy near-term momentum.'
            : 'Current daily pace is below the month-to-date run rate, signaling a need to push near-term sales activity.',
        lowStockRate > 0
            ? `Inventory pressure is present with ${dashboardReportData.stocks.lowStockItems} item(s) already below minimum levels.`
            : 'No current low-stock pressure detected in active inventory.'
    ];

    interpretationLines.forEach((line) => {
        cursorY = writeParagraph(`- ${line}`, cursorY);
        cursorY += 4;
    });

    cursorY += 6;
    sectionTitle('Recommended Actions (Next 7 Days)', cursorY);
    cursorY += 12;

    doc.autoTable({
        startY: cursorY,
        head: [['Priority', 'Action', 'Expected Impact']],
        body: [
            ['High', collectionsRiskLabel === 'High' ? 'Run payment follow-ups for pending bills daily.' : 'Maintain payment reminders for partial and pending bills.', 'Faster cash conversion and lower receivable risk'],
            ['High', inventoryRiskLabel === 'High' ? 'Create urgent replenishment orders for top shortage items.' : 'Review reorder levels and restock low-stock items.', 'Reduced stock-outs and fewer lost sales'],
            ['Medium', runRateDelta < 0 ? 'Launch a short-term sales push (bundles, upsell scripts, repeat buyer calls).' : 'Sustain current sales pace with targeted upsells on high-margin items.', 'Improved average ticket size and stronger weekly revenue']
        ],
        theme: 'striped',
        styles: { fontSize: 9, cellPadding: 6 },
        headStyles: { fillColor: [22, 163, 74] },
        columnStyles: {
            0: { cellWidth: 70 },
            1: { cellWidth: 280 },
            2: { cellWidth: 165 }
        }
    });

    doc.setFont('helvetica', 'italic');
    doc.setFontSize(9);
    doc.setTextColor(107, 114, 128);
    doc.text('This report highlights business signals and action priorities instead of detailed transaction listings.', 40, 800);

    const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
    doc.save(`dashboard-business-insights-${stamp}.pdf`);
}
</script>

<?php include 'footer.php'; ?>
