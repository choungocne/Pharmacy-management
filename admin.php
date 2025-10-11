<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DASHMIN - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --sidebar-bg: #4e73df;
            --sidebar-text: rgba(255,255,255,.8);
            --sidebar-hover: rgba(255,255,255,.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fc;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: all 0.3s;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu ul {
            list-style: none;
        }

        .sidebar-menu li a {
            padding: 12px 20px;
            display: block;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu li a:hover {
            background: var(--sidebar-hover);
            color: white;
        }

        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e3e6f0;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            background-color: #ddd;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 20px;
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-title {
            font-size: 0.9rem;
            color: var(--secondary);
            text-transform: uppercase;
            font-weight: 700;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card-icon.primary {
            background: var(--primary);
        }

        .card-icon.success {
            background: var(--success);
        }

        .card-icon.warning {
            background: var(--warning);
        }

        .card-icon.info {
            background: var(--info);
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 20px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .chart-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark);
        }

        .chart-container {
            height: 250px;
            position: relative;
        }

        .chart-bars {
            display: flex;
            align-items: flex-end;
            height: 200px;
            margin-bottom: 20px;
            gap: 10px;
        }

        .chart-bar {
            flex: 1;
            background: var(--primary);
            border-radius: 4px 4px 0 0;
            position: relative;
        }

        .chart-bar-label {
            position: absolute;
            bottom: -25px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.8rem;
            color: var(--secondary);
        }

        /* Recent Sales Table */
        .recent-sales {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 20px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e3e6f0;
        }

        th {
            color: var(--secondary);
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status.paid {
            background: rgba(28, 200, 138, 0.2);
            color: var(--success);
        }

        .btn {
            padding: 5px 10px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .btn:hover {
            background: #3a5ccc;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
                text-align: center;
            }

            .sidebar-header h2, .sidebar-menu li a span {
                display: none;
            }

            .sidebar-menu li a i {
                margin-right: 0;
                font-size: 1.2rem;
            }

            .main-content {
                margin-left: 80px;
            }

            .charts-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>DASHMIN</h2>
        </div>
        <div class="sidebar-menu">
            <ul>
                <li><a href="#"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
                <li><a href="#"><i class="fas fa-chart-area"></i> <span>Charts</span></a></li>
                <li><a href="#"><i class="fas fa-table"></i> <span>Tables</span></a></li>
                <li><a href="#"><i class="fas fa-wallet"></i> <span>Revenue</span></a></li>
                <li><a href="#"><i class="fas fa-shopping-cart"></i> <span>Sales</span></a></li>
                <li><a href="#"><i class="fas fa-user"></i> <span>Customers</span></a></li>
                <li><a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1>Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iIzVhNWM2OSI+PHBhdGggZD0iTTEyIDJDNi40OCAyIDIgNi40OCAyIDEyczQuNDggMTAgMTAgMTAgMTAtNC40OCAxMC0xMFMxNy41MiAyIDEyIDJ6bTAgM2MzLjMxIDAgNiAyLjY5IDYgNnMtMi42OSA2LTYgNi02LTIuNjktNi02IDIuNjktNiA2LTZ6bTAgMTdjLTIuNjcgMC04IDEuMzQtOCA0aDE2YzAtMi42Ni01LjMzLTQtOC00eiIvPjwvc3ZnPg==" alt="User">
                </div>
                <div class="user-details">
                    <h3>John Doe</h3>
                    <p>Admin</p>
                </div>
            </div>
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Today Sale</div>
                    <div class="card-icon primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="card-value">$1234</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Total Sale</div>
                    <div class="card-icon success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <div class="card-value">$1234</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Today Revenue</div>
                    <div class="card-icon warning">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="card-value">$1234</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Total Revenue</div>
                    <div class="card-icon info">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="card-value">$1234</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">Worldwide Sales</div>
                    <button class="btn">Show All</button>
                </div>
                <div class="chart-container">
                    <div class="chart-bars" id="worldwide-sales-chart">
                        <!-- Bars will be generated by JavaScript -->
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">Sales & Revenue</div>
                    <button class="btn">Show All</button>
                </div>
                <div class="chart-container">
                    <div class="chart-bars" id="sales-revenue-chart">
                        <!-- Bars will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Sales Table -->
        <div class="recent-sales">
            <div class="table-header">
                <h2>Recent Sales</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>01 Jan 2045</td>
                        <td>INV-0123</td>
                        <td>Jhon Doe</td>
                        <td>$123</td>
                        <td><span class="status paid">Paid</span></td>
                        <td><button class="btn">Detail</button></td>
                    </tr>
                    <tr>
                        <td>01 Jan 2045</td>
                        <td>INV-0123</td>
                        <td>Jhon Doe</td>
                        <td>$123</td>
                        <td><span class="status paid">Paid</span></td>
                        <td><button class="btn">Detail</button></td>
                    </tr>
                    <tr>
                        <td>01 Jan 2045</td>
                        <td>INV-0123</td>
                        <td>Jhon Doe</td>
                        <td>$123</td>
                        <td><span class="status paid">Paid</span></td>
                        <td><button class="btn">Detail</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Data for charts
        const worldwideSalesData = [100, 80, 60, 40, 20, 0];
        const salesRevenueData = [300, 200, 160, 180, 90, 0];
        const years = ['2016', '2017', '2018', '2019', '2020', '2021', '2022'];

        // Function to create chart bars
        function createChartBars(containerId, data, maxValue) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            data.forEach((value, index) => {
                const barHeight = (value / maxValue) * 100;
                const bar = document.createElement('div');
                bar.className = 'chart-bar';
                bar.style.height = `${barHeight}%`;

                const label = document.createElement('div');
                label.className = 'chart-bar-label';
                label.textContent = years[index];

                bar.appendChild(label);
                container.appendChild(bar);
            });
        }

        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            createChartBars('worldwide-sales-chart', worldwideSalesData, 100);
            createChartBars('sales-revenue-chart', salesRevenueData, 300);
        });
    </script>
</body>
</html>
