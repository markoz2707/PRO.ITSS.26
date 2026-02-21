<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'ITSS Project Management'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: #0078d4;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 500;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 60px;
            bottom: 0;
            width: 250px;
            background: white;
            border-right: 1px solid #e1e1e1;
            overflow-y: auto;
        }

        .nav-menu {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-menu li a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }

        .nav-menu li a:hover {
            background: #f3f3f3;
        }

        .nav-menu li a.active {
            background: #e3f2fd;
            color: #0078d4;
            border-left: 3px solid #0078d4;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 60px);
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card-header {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e1e1e1;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #0078d4;
            color: white;
        }

        .btn-primary:hover {
            background: #005a9e;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #0078d4;
            box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.1);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 3px;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #0078d4;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .badge-secondary {
            background: #e9ecef;
            color: #495057;
        }

        .badge-purple {
            background: #e8dff5;
            color: #6f42c1;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #e1e1e1;
            margin-bottom: 1.5rem;
            gap: 0;
        }

        .tab {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.2s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-size: 0.9rem;
        }

        .tab:hover {
            color: #0078d4;
        }

        .tab.active {
            color: #0078d4;
            border-bottom-color: #0078d4;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .match-card {
            background: white;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.2s;
        }

        .match-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .match-columns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
        }

        .match-column {
            padding: 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .match-column.crm {
            background: #e3f2fd;
            border-left: 3px solid #0078d4;
        }

        .match-column.contract {
            background: #fff3e0;
            border-left: 3px solid #ff9800;
        }

        .match-column.sd-project {
            background: #e8f5e9;
            border-left: 3px solid #4caf50;
        }

        .match-column h4 {
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .confidence-bar {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .confidence-fill {
            height: 6px;
            border-radius: 3px;
            width: 60px;
            background: #e9ecef;
            overflow: hidden;
        }

        .confidence-fill-inner {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }

        .merge-fields {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e1e1e1;
        }

        .merge-field-row {
            display: flex;
            align-items: center;
            padding: 0.35rem 0;
            gap: 0.75rem;
            font-size: 0.85rem;
        }

        .merge-field-row input[type="checkbox"] {
            flex-shrink: 0;
        }

        .field-label {
            font-weight: 500;
            min-width: 160px;
        }

        .field-current {
            color: #6c757d;
            min-width: 120px;
        }

        .field-arrow {
            color: #0078d4;
            font-weight: bold;
        }

        .field-new {
            color: #28a745;
            font-weight: 500;
        }

        .action-bar {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn-warning {
            background: #ff9800;
            color: white;
        }

        .btn-warning:hover {
            background: #e68a00;
        }

        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .loading-spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #e1e1e1;
            border-top: 2px solid #0078d4;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ITSS Project Management</h1>
        <div class="user-info">
            <span><?php echo \ITSS\Core\Session::get('user_name', 'User'); ?></span>
            <a href="/auth/logout" class="btn btn-secondary">Wyloguj</a>
        </div>
    </div>

    <div class="sidebar">
        <ul class="nav-menu">
            <li><a href="/dashboard">Dashboard</a></li>
            <li><a href="/projects">Projekty</a></li>
            <li><a href="/reconciliation">Uspójnianie danych</a></li>
            <li><a href="/invoices">Faktury</a></li>
            <li><a href="/documents">Dokumenty</a></li>
            <li><a href="/leaves">Wnioski urlopowe</a></li>
            <li><a href="/bonuses">Premie</a></li>
            <li><a href="/czasomat">Czasomat</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php echo $content ?? ''; ?>
    </div>

    <script>
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-menu a').forEach(link => {
            if (link.getAttribute('href') === currentPath ||
                (currentPath !== '/' && currentPath.startsWith(link.getAttribute('href')))) {
                link.classList.add('active');
            }
        });
    </script>
</body>
</html>
