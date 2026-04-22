<?php
$pageTitleMap = [
    'dashboard' => 'Dashboard',
    'retail' => 'Retail Billing',
    'event' => 'Event Billing',
    'products' => 'Stocks',
    'suppliers' => 'Suppliers',
    'employees' => 'Employees',
    'categories' => 'Categories',
    'customers' => 'Customers',
    'reports' => 'Reports',
    'settings' => 'Settings',
];
$currentPageTitle = $pageTitleMap[$page] ?? ucfirst($page);
$currentAction = $_GET['action'] ?? 'index';
$showReportButton = in_array($page, ['retail', 'event', 'products', 'suppliers', 'employees']) && $currentAction === 'index';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        (function () {
            const storedTheme = localStorage.getItem('app-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = storedTheme || (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $settings['company_name'] ?? 'Sri Ram Fire Works' ?> - Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --sidebar-width: 250px;
            --app-bg: #f4f7ff;
            --app-text: #1f2937;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
            --surface-elevated: #ffffff;
            --border-soft: #e5e7eb;
            --border-strong: #dbe3f0;
            --shadow-soft: 0 2px 10px rgba(15,23,42,0.08);
        }
        body {
            background: linear-gradient(180deg, var(--app-bg) 0%, #eef4ff 100%);
            color: var(--app-text);
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border-soft);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .sidebar .brand { padding: 28px 20px 20px; color: #243b7b; border-bottom: 1px solid #eef2ff; }
        .sidebar .brand-logo { width: 42px; height: 42px; object-fit: cover; border-radius: 50%; border: 1px solid #d1d5db; }
        .sidebar .brand small { color: #94a3b8 !important; }
        .sidebar .nav-link { color: #94a3b8; padding: 12px 20px; margin: 2px 12px; border-radius: 10px; border-left: 3px solid transparent; font-weight: 500; transition: background-color 0.15s ease, color 0.15s ease, transform 0.15s ease; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: var(--primary-color); background: #eef2ff; border-left-color: var(--primary-color); transform: translateX(1px); }
        .sidebar .nav-link i { width: 25px; }
        .sidebar .logout-link { margin-top: auto; margin-bottom: 20px; color: #ef4444 !important; }
        .sidebar .logout-link:hover { background: #fee2e2; border-left-color: #ef4444; }
        .main-content { margin-left: var(--sidebar-width); padding: 20px; }
        .top-bar { background: var(--surface); padding: 15px 20px; margin: -20px -20px 20px; box-shadow: var(--shadow-soft); border-bottom: 1px solid var(--border-soft); }
        .card { border: none; background: var(--surface); box-shadow: var(--shadow-soft); }
        .dropdown-menu { border-color: var(--border-soft); box-shadow: 0 12px 28px rgba(15,23,42,0.12); }
        .modal-content { background: var(--surface); }
        .form-control,
        .form-select,
        .input-group-text {
            background-color: var(--surface);
            color: var(--app-text);
            border-color: var(--border-soft);
        }
        .form-control:focus,
        .form-select:focus {
            border-color: rgba(79, 70, 229, 0.45);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.12);
        }
        .btn-outline-primary,
        .btn-outline-secondary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-warning,
        .btn-outline-info {
            border-width: 1px;
        }
        .stat-card { border-left: 4px solid var(--primary-color); }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
        .theme-switcher {
            margin: 0 12px 12px;
            padding: 12px;
            border-radius: 12px;
            background: var(--surface-muted);
            border: 1px solid var(--border-soft);
        }
        .theme-switcher .btn {
            border-radius: 10px;
        }
        [data-bs-theme="dark"] body {
            --app-bg: #0b1220;
            --app-text: #e5eefb;
            --surface: #111827;
            --surface-muted: #0f172a;
            --surface-elevated: #172033;
            --border-soft: #243244;
            --border-strong: #334155;
            --shadow-soft: 0 16px 34px rgba(0,0,0,0.24);
            background: linear-gradient(180deg, #0b1220 0%, #0f172a 100%);
            color: var(--app-text);
        }
        [data-bs-theme="dark"] .sidebar {
            background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
            border-right-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .sidebar .brand {
            color: #f8fafc;
            border-bottom-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .sidebar .brand small {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .sidebar .nav-link {
            color: #cbd5e1;
        }
        [data-bs-theme="dark"] .sidebar .nav-link:hover,
        [data-bs-theme="dark"] .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(79,70,229,0.18), rgba(15,23,42,0.15));
            color: #ffffff;
        }
        [data-bs-theme="dark"] .sidebar .logout-link:hover {
            background: rgba(239,68,68,0.16);
        }
        [data-bs-theme="dark"] .theme-switcher {
            background: rgba(15, 23, 42, 0.82);
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .top-bar {
            background: rgba(17, 24, 39, 0.92);
            color: #e2e8f0;
            box-shadow: 0 18px 36px rgba(0,0,0,0.22);
            border-bottom-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .card {
            background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
            color: #e2e8f0;
            box-shadow: 0 16px 32px rgba(0,0,0,0.2);
            border: 1px solid rgba(148, 163, 184, 0.12);
        }
        [data-bs-theme="dark"] .bg-white,
        [data-bs-theme="dark"] .bg-light,
        [data-bs-theme="dark"] .card-header.bg-white,
        [data-bs-theme="dark"] .card-header.bg-light,
        [data-bs-theme="dark"] .table-light,
        [data-bs-theme="dark"] .list-group-item,
        [data-bs-theme="dark"] .modal-header,
        [data-bs-theme="dark"] .modal-footer,
        [data-bs-theme="dark"] .alert-light,
        [data-bs-theme="dark"] .dropdown-menu,
        [data-bs-theme="dark"] .offcanvas,
        [data-bs-theme="dark"] .accordion-item,
        [data-bs-theme="dark"] .accordion-button,
        [data-bs-theme="dark"] .nav-tabs .nav-link,
        [data-bs-theme="dark"] .pagination .page-link,
        [data-bs-theme="dark"] .input-group-text,
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background-color: #111827 !important;
            color: #e2e8f0 !important;
            border-color: var(--border-soft) !important;
        }
        [data-bs-theme="dark"] .bg-white.text-dark,
        [data-bs-theme="dark"] .bg-light.text-dark,
        [data-bs-theme="dark"] .bg-white .text-dark,
        [data-bs-theme="dark"] .bg-light .text-dark,
        [data-bs-theme="dark"] .card-header.bg-white .text-dark,
        [data-bs-theme="dark"] .card-header.bg-light .text-dark,
        [data-bs-theme="dark"] .table-light .text-dark,
        [data-bs-theme="dark"] .alert-light .text-dark,
        [data-bs-theme="dark"] .dropdown-menu .text-dark,
        [data-bs-theme="dark"] .modal-header .text-dark,
        [data-bs-theme="dark"] .modal-footer .text-dark {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .bg-white.text-muted,
        [data-bs-theme="dark"] .bg-light.text-muted,
        [data-bs-theme="dark"] .card-header.bg-white .text-muted,
        [data-bs-theme="dark"] .card-header.bg-light .text-muted,
        [data-bs-theme="dark"] .table-light .text-muted,
        [data-bs-theme="dark"] .alert-light .text-muted,
        [data-bs-theme="dark"] .dropdown-menu .text-muted,
        [data-bs-theme="dark"] .modal-header .text-muted,
        [data-bs-theme="dark"] .modal-footer .text-muted {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .accordion-button:not(.collapsed) {
            background-color: #172033 !important;
            color: #ffffff !important;
            box-shadow: none;
        }
        [data-bs-theme="dark"] .accordion-button::after {
            filter: invert(1) grayscale(100%);
        }
        [data-bs-theme="dark"] .nav-tabs {
            border-bottom-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .nav-tabs .nav-link {
            background-color: #0f172a;
            color: #cbd5e1;
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .nav-tabs .nav-link.active {
            background-color: #172033;
            color: #ffffff;
            border-color: var(--border-soft) var(--border-soft) #172033;
        }
        [data-bs-theme="dark"] .pagination .page-link {
            background-color: #0f172a;
            color: #cbd5e1;
        }
        [data-bs-theme="dark"] .pagination .page-item.active .page-link {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
        }
        [data-bs-theme="dark"] .dropdown-menu {
            background: #111827;
            color: #e2e8f0;
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .dropdown-item {
            color: #e2e8f0;
        }
        [data-bs-theme="dark"] .dropdown-item:hover,
        [data-bs-theme="dark"] .dropdown-item:focus {
            background: rgba(79,70,229,0.16);
            color: #ffffff;
        }
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select,
        [data-bs-theme="dark"] .input-group-text {
            background-color: #0f172a;
            color: #e5eefb;
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .form-control::placeholder {
            color: #7f8ca3;
        }
        [data-bs-theme="dark"] .btn-outline-primary {
            color: #9fb4ff;
            border-color: rgba(79,70,229,0.65);
        }
        [data-bs-theme="dark"] .btn-outline-primary:hover,
        [data-bs-theme="dark"] .btn-outline-primary:focus {
            background: #4f46e5;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .btn-outline-secondary {
            color: #d1d5db;
            border-color: #475569;
        }
        [data-bs-theme="dark"] .btn-outline-secondary:hover,
        [data-bs-theme="dark"] .btn-outline-secondary:focus {
            background: #334155;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .btn-outline-success {
            color: #86efac;
            border-color: rgba(34,197,94,0.65);
        }
        [data-bs-theme="dark"] .btn-outline-success:hover,
        [data-bs-theme="dark"] .btn-outline-success:focus {
            background: #16a34a;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .btn-outline-danger {
            color: #fda4af;
            border-color: rgba(239,68,68,0.65);
        }
        [data-bs-theme="dark"] .btn-outline-danger:hover,
        [data-bs-theme="dark"] .btn-outline-danger:focus {
            background: #dc2626;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .btn-outline-warning {
            color: #fde68a;
            border-color: rgba(245,158,11,0.75);
        }
        [data-bs-theme="dark"] .btn-outline-warning:hover,
        [data-bs-theme="dark"] .btn-outline-warning:focus {
            background: #d97706;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .btn-outline-info {
            color: #7dd3fc;
            border-color: rgba(6,182,212,0.65);
        }
        [data-bs-theme="dark"] .btn-outline-info:hover,
        [data-bs-theme="dark"] .btn-outline-info:focus {
            background: #0891b2;
            color: #ffffff;
        }
        [data-bs-theme="dark"] .text-muted {
            color: #94a3b8 !important;
        }
        [data-bs-theme="dark"] .table {
            color: #e2e8f0;
        }
        [data-bs-theme="dark"] .table-light,
        [data-bs-theme="dark"] .table > :not(caption) > * > * {
            background-color: #0f172a;
            color: #e2e8f0;
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .table thead th {
            color: #cbd5e1;
            background: #111827;
        }
        [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            color: #e2e8f0;
            background-color: rgba(17, 24, 39, 0.95);
        }
        [data-bs-theme="dark"] .alert {
            border-color: var(--border-soft);
            background: #111827;
            color: #e2e8f0;
        }
        [data-bs-theme="dark"] .alert-info {
            background: rgba(14, 165, 233, 0.12);
            color: #cffafe;
        }
        [data-bs-theme="dark"] .alert-success {
            background: rgba(34, 197, 94, 0.12);
            color: #dcfce7;
        }
        [data-bs-theme="dark"] .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            color: #fee2e2;
        }
        [data-bs-theme="dark"] .alert-warning {
            background: rgba(245, 158, 11, 0.12);
            color: #fef3c7;
        }
        [data-bs-theme="dark"] .badge.bg-primary,
        [data-bs-theme="dark"] .badge.bg-secondary,
        [data-bs-theme="dark"] .badge.bg-success,
        [data-bs-theme="dark"] .badge.bg-warning,
        [data-bs-theme="dark"] .badge.bg-danger,
        [data-bs-theme="dark"] .badge.bg-info {
            box-shadow: 0 0 0 1px rgba(255,255,255,0.05) inset;
        }
        [data-bs-theme="dark"] .modal-content {
            background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
            color: #e2e8f0;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }
        [data-bs-theme="dark"] .modal-header,
        [data-bs-theme="dark"] .modal-footer {
            border-color: var(--border-soft);
        }
        [data-bs-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%);
        }
        @media print {
            .sidebar, .top-bar .btn, .logout-link, .btn, .no-print { display: none !important; }
            .main-content { margin-left: 0; padding: 0; }
            .top-bar { margin: 0 0 20px; box-shadow: none; padding-left: 0; }
            body { background: #fff; }
            .card { box-shadow: none; border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="brand d-flex align-items-center">
            <img src="WhatsApp%20Image%202026-03-30%20at%2021.39.04.jpeg" alt="Brand Logo" class="brand-logo me-2">
            <div>
                <h6 class="mb-0">Sri Ram Fire Works</h6>
                <small class="text-muted">Billing System</small>
            </div>
        </div>
        <nav class="nav flex-column mt-3 flex-grow-1">
            <a href="?page=dashboard" class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a href="?page=retail" class="nav-link <?= $page == 'retail' ? 'active' : '' ?>"><i class="bi bi-cart me-2"></i>Retail Billing</a>
            <a href="?page=event" class="nav-link <?= in_array($page, ['event', 'wholesale']) ? 'active' : '' ?>"><i class="bi bi-calendar-event me-2"></i>Event Billing</a>
            <a href="?page=products" class="nav-link <?= $page == 'products' ? 'active' : '' ?>"><i class="bi bi-box-seam me-2"></i>Stocks</a>
            <a href="?page=suppliers" class="nav-link <?= $page == 'suppliers' ? 'active' : '' ?>"><i class="bi bi-clipboard2-check me-2"></i>Suppliers</a>
            <a href="?page=employees" class="nav-link <?= $page == 'employees' ? 'active' : '' ?>"><i class="bi bi-person-workspace me-2"></i>Employees</a>
            <a href="?page=settings" class="nav-link <?= $page == 'settings' ? 'active' : '' ?>"><i class="bi bi-gear me-2"></i>Settings</a>
            <div class="theme-switcher">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <small class="text-muted fw-semibold">Theme</small>
                    <small class="text-muted" id="themeLabel">Light</small>
                </div>
                <div class="btn-group w-100" role="group" aria-label="Theme selector">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="lightThemeBtn"><i class="bi bi-sun me-1"></i>Light</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="darkThemeBtn"><i class="bi bi-moon-stars me-1"></i>Dark</button>
                </div>
            </div>
            <hr class="my-3 mx-3 border-secondary">
            <a href="?logout=1" class="nav-link logout-link"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <?php if (!in_array($page, ['dashboard', 'retail', 'event', 'products', 'categories', 'customers', 'suppliers', 'employees', 'settings'])): ?>
        <div class="top-bar d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= htmlspecialchars($currentPageTitle) ?></h5>
            <div class="d-flex align-items-center gap-3">
                <?php if ($showReportButton): ?>
                <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-download me-2"></i>Download Report</button>
                <?php endif; ?>
                <span class="me-3"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                <span class="badge bg-primary"><?= ucfirst($_SESSION['user_role'] ?? 'admin') ?></span>
            </div>
        </div>
        <?php endif; ?>
