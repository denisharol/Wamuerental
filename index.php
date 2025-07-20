<?php
session_start();
// Security: Prevent page caching after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin/login.php");
    exit;
}

// Avatar logic (same as Users/index.php)
require_once 'db.php';
$adminId = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$name = 'Administrator'; // Hardcoded name
$email = strtolower($user['email']);
$initials = 'AD'; // For "Administrator"

// Default page
$page = isset($_GET['page']) ? $_GET['page'] : 'Admindashboard';
$pageFile = "Admin/" . $page . ".php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        /* Additional styles for sidebar links and dot */
        .subtopic a {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            text-decoration: none;
            color: #4a5568; /* Default grey */
            font-weight: normal;
            font-size: 0.9rem;
            transition: color 0.2s;
            position: relative;
        }
        .subtopic a .dot {
            display: none;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #d1d5db; /* Grey by default */
            margin-right: 10px;
            transition: background 0.2s;
        }
        .subtopic a i {
            margin-right: 0.5rem;
            color: inherit;
            transition: color 0.2s;
        }
        .subtopic a.active,
        .subtopic a:hover {
            color: #2563eb; /* Blue for active/hover */
        }
        .subtopic a.active i,
        .subtopic a:hover i {
            color: #2563eb;
        }
        .subtopic a.active .dot,
        .subtopic a:hover .dot {
            display: inline-block;
            background: #2563eb; /* Blue dot for active/hover */
        }
        .subtopic a:not(.active):hover .dot {
            background: #2563eb;
        }
        .subtopic a:not(.active) .dot {
            background: #d1d5db;
        }
        .subtopic a.active {
            background: none;
            border-radius: 0;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-white">
    <div class="flex">
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header flex flex-row items-center px-0 pt-4 pb-2 space-x-3">
                <div class="bg-gray-400 text-white font-bold rounded-lg flex items-center justify-center text-xs" style="width: 1.25rem; height: 1.25rem;"><?php echo $initials; ?></div>
                <div class="text-sm font-semibold username"><?php echo $name; ?></div>
                <img src="/Demo/images/userprofile.jpg" alt="profile" class="w-8 h-8 rounded-full object-cover" />
            </div>

            <div class="sidebar-content">
                <div class="topic">
                    <h2>Dashboard</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('Admindashboard', this)"><span class="dot"></span><i class="fas fa-chart-line"></i> Dashboard</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Account Operations</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('create_account', this)"><span class="dot"></span><i class="fas fa-user-plus"></i> Create Account</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Property Operations</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('Properties', this)"><span class="dot"></span><i class="fas fa-home"></i> Properties</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Units', this)"><span class="dot"></span><i class="fas fa-door-open"></i> Units</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Tenants', this)"><span class="dot"></span><i class="fas fa-users"></i> Tenants</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Maintenance', this)"><span class="dot"></span><i class="fas fa-wrench"></i> Maintenance</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('vacating_notice', this)"><span class="dot"></span><i class="fas fa-file-export"></i> Vacating Notice</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('approved_vacating_notices', this)"><span class="dot"></span><i class="fas fa-check"></i> Approved Notices</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Financial Operations</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('Invoices', this)"><span class="dot"></span><i class="fas fa-file-invoice"></i> Invoices</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Payments', this)"><span class="dot"></span><i class="fas fa-receipt"></i> Billing</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Expenses', this)"><span class="dot"></span><i class="fas fa-money-bill-wave"></i> Expenses</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('Transactions', this)"><span class="dot"></span><i class="fas fa-gbp"></i> Transactions</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Communications</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('Messaging', this)"><span class="dot"></span><i class="fas fa-envelope"></i> Messaging</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Reports</h2>
                    <ul>
                        <li class="subtopic"><a href="#" onclick="loadPage('Reports', this)"><span class="dot"></span><i class="fas fa-file-alt"></i> Reports</a></li>
                        <li class="subtopic"><a href="#" onclick="loadPage('AssesmentReport', this)"><span class="dot"></span><i class="fas fa-file"></i> Assesment Reports</a></li>
                    </ul>
                </div>

                <div class="topic">
                    <h2>Admin Operations</h2>
                    <ul>
                        <li class="subtopic"><a href="Admin/logout.php"><span class="dot"></span><i class="fas fa-power-off"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </aside>
        <main id="main-content" class="flex-grow p-4">
            <iframe id="content-frame" src="<?php echo htmlspecialchars($pageFile); ?>" frameborder="0" class="w-full h-screen"></iframe>
        </main>
    </div>
    <script>
        const allowedPages = [
            'Admindashboard', 'create_account', 'Properties', 'Units', 'Tenants',
            'Maintenance', 'vacating_notice', 'approved_vacating_notices',
            'Invoices', 'Payments', 'Expenses', 'Transactions',
            'Messaging', 'Reports', 'AssesmentReport'
        ];

        function loadPage(page, element = null) {
            if (allowedPages.includes(page)) {
                document.getElementById('content-frame').src = 'Admin/' + page + '.php';
                history.pushState({ page: page }, '', '?page=' + page);
                highlightActive(element);
            } else {
                alert('Invalid page requested.');
            }
        }

        function highlightActive(clickedElement) {
            const links = document.querySelectorAll('.subtopic a');
            links.forEach(link => link.classList.remove('active'));
            if (clickedElement) {
                clickedElement.classList.add('active');
            }
        }

        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.page) {
                const page = event.state.page;
                if (allowedPages.includes(page)) {
                    document.getElementById('content-frame').src = 'Admin/' + page + '.php';
                    const links = document.querySelectorAll('.subtopic a');
                    links.forEach(link => {
                        if (link.getAttribute('onclick').includes(page)) {
                            highlightActive(link);
                        }
                    });
                }
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const page = urlParams.get('page') || 'Admindashboard';
            if (allowedPages.includes(page)) {
                document.getElementById('content-frame').src = 'Admin/' + page + '.php';
                const links = document.querySelectorAll('.subtopic a');
                links.forEach(link => {
                    if (link.getAttribute('onclick').includes(page)) {
                        highlightActive(link);
                    }
                });
            } else {
                document.getElementById('content-frame').src = 'Admin/Admindashboard.php';
            }
        });

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('hidden');
        }
        function openSettings() {
            document.getElementById('content-frame').src = 'Admin/settings.php';
            history.pushState({ page: 'settings' }, '', '?page=settings');
            const links = document.querySelectorAll('.subtopic a');
            links.forEach(link => link.classList.remove('active'));
            const dropdown = document.getElementById('dropdown');
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        }
        window.onclick = function(event) {
            if (!event.target.matches('img')) {
                const dropdown = document.getElementById('dropdown');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                }
            }
        }
    </script>
</body>
</html>