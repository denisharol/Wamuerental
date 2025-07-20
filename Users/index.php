<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$name = $user['name'];
$email = strtolower($user['email']);
$initials = implode('', array_map(fn($n) => strtoupper($n[0]), explode(' ', $name)));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Sidebar</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        #dropdown {
            left: 90%;
            transform: translateX(-90%);
            width: 14rem;
        }
        /* Dark mode for sidebar and dropdown */
        .sidebar-dark {
            background-color: #000 !important;
            color: #fff !important;
        }
        .sidebar-dark .sidebar-title,
        .sidebar-dark .sidebar-link,
        .sidebar-dark .username {
            color: #fff !important;
        }
        .sidebar-dark .circle {
            background-color: #222 !important;
            color: #fff !important;
        }
        .dropdown-dark {
            background-color: #000 !important;
            color: #fff !important;
        }
        .dropdown-dark .border-b,
        .dropdown-dark .text-gray-700,
        .dropdown-dark .text-gray-600 {
            color: #fff !important;
            border-color: #333 !important;
        }
        .dropdown-dark .hover\:bg-gray-100:hover {
            background-color: #333 !important;
        }
    </style>
    <script>
        // Apply theme to the whole page and sidebar/dropdown
        function applyTheme() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.body.classList.toggle('dark', theme === 'dark');
            const sidebar = document.getElementById('sidebar');
            const dropdown = document.getElementById('dropdown');
            if (sidebar) sidebar.classList.toggle('sidebar-dark', theme === 'dark');
            if (dropdown) dropdown.classList.toggle('dropdown-dark', theme === 'dark');
            // Send theme to iframe
            const iframe = document.getElementById('content-frame');
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage({ type: 'theme', theme: theme }, '*');
            }
        }

        // Toggle theme and store in localStorage
        function toggleTheme() {
            const current = localStorage.getItem('theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', next);
            applyTheme();
        }

        // Listen for theme changes (e.g., from other tabs)
        window.addEventListener('storage', function(e) {
            if (e.key === 'theme') applyTheme();
        });

        // Listen for iframe load to apply theme
        window.addEventListener('DOMContentLoaded', function() {
            applyTheme();
            const iframe = document.getElementById('content-frame');
            if (iframe) {
                iframe.addEventListener('load', function() {
                    iframe.contentWindow.postMessage({ type: 'theme', theme: localStorage.getItem('theme') || 'light' }, '*');
                });
            }
        });

        // Listen for theme requests from iframe
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'request-theme') {
                event.source.postMessage({ type: 'theme', theme: localStorage.getItem('theme') || 'light' }, '*');
            }
        });
    </script>
</head>
<body class="bg-white transition-colors duration-300">
    <div class="flex">
        <aside id="sidebar" class="sidebar bg-gray-100 transition-all duration-300 w-64 relative">
            <div class="flex items-center justify-between px-4 pt-4 pb-2 space-x-3">
                <div class="flex items-center space-x-3">
                    <div class="bg-gray-400 text-white font-bold rounded-lg w-10 h-10 flex items-center justify-center"><?php echo $initials; ?></div>
                    <div class="text-sm font-semibold username"><?php echo $name; ?></div>
                </div>
                <div class="relative">
                    <img src="/Demo/images/userprofile.jpg" alt="profile" class="w-10 h-10 rounded-full cursor-pointer" onclick="toggleDropdown()" />
                    <div id="dropdown" class="hidden absolute mt-2 bg-white shadow-lg rounded-md z-20 text-sm">
                        <div class="border-b px-4 py-2 font-bold text-gray-700">Account</div>
                        <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-100 cursor-pointer">
                            <span>Account Settings</span>
                            <i class="fas fa-cog text-gray-500"></i>
                        </div>
                        <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-100 cursor-pointer border-b">
                            <span>Payment Method</span>
                            <i class="fas fa-credit-card text-gray-500"></i>
                        </div>
                        <div class="border-b px-3 py-2 text-gray-600 font-small"><?php echo $email; ?></div>
                        <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-100 cursor-pointer">
                            <span>User Settings</span>
                            <i class="fas fa-user-cog text-gray-500"></i>
                        </div>
                        <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-100 cursor-pointer border-b" onclick="toggleTheme()">
                            <span>Theme(Beta)</span>
                            <i class="fas fa-adjust text-gray-500"></i>
                        </div>
                        <div class="px-4 py-2 text-red-600 hover:bg-gray-100 cursor-pointer" onclick="logout()">Logout</div>
                    </div>
                </div>
            </div>

            <div class="sidebar-content mt-2">
                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Account Operation</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('UserProfile')">
                                <i class="fas fa-users"></i>
                                <span>User Profile</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Financial Operations</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('pay')">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Pay Rent</span>
                            </button>
                        </li>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Transactions')">
                                <i class="fas fa-dollar-sign"></i>
                                <span>Transactions</span>
                            </button>
                        </li>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Invoices')">
                                <i class="fas fa-file-invoice"></i>
                                <span>Invoices</span>
                            </button>
                        </li>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Receipts')">
                                <i class="fas fa-receipt"></i>
                                <span>Receipt</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Property Operations</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Maintenance')">
                                <i class="fas fa-wrench"></i>
                                <span>Maintenance</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Communications</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Messaging')">
                                <i class="fas fa-envelope"></i>
                                <span>Messaging</span>
                            </button>
                        </li>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Notice')">
                                <i class="fas fa-door-open"></i>
                                <span>Vacation Notice</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Reports</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="loadPage('Report')">
                                <i class="fas fa-envelope"></i>
                                <span>Assessment Report</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="topic px-4">
                    <h2 class="text-sm font-semibold sidebar-title">Logout</h2>
                    <ul>
                        <li class="subtopic">
                            <button class="flex items-center space-x-2 py-2 w-full sidebar-link" onclick="logout()">
                                <i class="fas fa-power-off"></i>
                                <span>Logout</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <main id="main-content" class="flex-grow p-4">
            <iframe id="content-frame" src="" frameborder="0" class="w-full h-screen"></iframe>
        </main>
    </div>

    <script>
        function loadPage(page) {
            const allowedPages = {
                'UserProfile': '/Demo/Users/UserProfile.php',
                'pay': '/Demo/Users/pay.php',
                'Transactions': '/Demo/Users/Transactions.php',
                'Invoices': '/Demo/Users/Invoices.php',
                'Receipts': '/Demo/Users/Receipts.php',
                'Maintenance': '/Demo/Users/Maintenance.php',
                'Messaging': '/Demo/Users/Messaging.php',
                'Notice': '/Demo/Users/Notice.php',
                'Report': '/Demo/Users/Report.php'
            };

            if (allowedPages[page]) {
                document.getElementById('content-frame').src = allowedPages[page];
                history.pushState(null, '', '?page=' + page);
            } else {
                console.error('Invalid page:', page);
            }
        }

        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.classList.toggle('hidden');
        }

        function logout() {
            window.location.href = '/Demo/Users/logout.php';
        }

        window.onclick = function(event) {
            if (!event.target.matches('img')) {
                const dropdown = document.getElementById('dropdown');
                if (!dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                }
            }
        }

        // On page load, check if URL contains a page parameter
        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            const page = params.get('page');
            if (page) {
                loadPage(page);
            } else {
                loadPage('UserProfile'); // Default page
            }
        }
    </script>
</body>
</html>