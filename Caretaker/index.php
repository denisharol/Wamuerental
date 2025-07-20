<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['caretaker_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch caretaker details
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT name FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_name);
$stmt->fetch();
$stmt->close();

// Generate abbreviation (e.g., "John Doe" -> "JD")
$abbreviation = "";
if (!empty($caretaker_name)) {
    $name_parts = explode(" ", $caretaker_name);
    foreach ($name_parts as $part) {
        $abbreviation .= strtoupper($part[0]);
    }
}

// Determine the page to load
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard</title>
  <link rel="stylesheet" href="sidebar.css">
  <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar" class="sidebar active">
  <!-- Avatar -->
  <div class="avatar">
    <div class="circle"><?php echo htmlspecialchars($abbreviation); ?></div>
    <div class="username"><?php echo htmlspecialchars($caretaker_name); ?></div>
  </div>

  <div class="sidebar-content">
    <!-- Dashboard Section -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="layers" class="icon"></i> Dashboard
      </div>
      <a href="index.php?page=dashboard" class="sidebar-link">Dashboard</a>
    </div>

    <!-- Account Operations -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="user-check" class="icon"></i> Account Operations
      </div>
      <a href="index.php?page=createuser" class="sidebar-link">Create Account</a>
    </div>

    <!-- Property Operations -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="home" class="icon"></i> Property Operations
      </div>
      <a href="index.php?page=units" class="sidebar-link">Units</a>
      <a href="index.php?page=tenants" class="sidebar-link">Tenants</a>
      <a href="index.php?page=maintenance" class="sidebar-link">Maintenance</a>
      <a href="index.php?page=vacating_notice" class="sidebar-link">Vacating Notice</a>
      <a href="index.php?page=approved_vacating_notices" class="sidebar-link">Approve Notice</a>
    </div>

    <!-- Financial Operations -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="trending-up" class="icon"></i> Financial Operations
      </div>
      <a href="index.php?page=invoices" class="sidebar-link">Invoices</a>
      <a href="index.php?page=billing" class="sidebar-link">Billing</a>
      <a href="index.php?page=expenses" class="sidebar-link">Expenses</a>
      <a href="index.php?page=transactions" class="sidebar-link">Transactions</a>
    </div>

    <!-- Communication -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="message-circle" class="icon"></i> Communication
      </div>
      <a href="index.php?page=messaging" class="sidebar-link">Messaging</a>
    </div>

    <!-- Reports -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="bar-chart-2" class="icon"></i> Reports
      </div>
      <a href="index.php?page=assesmentreport" class="sidebar-link">Vacating Assessment Reports</a>
    </div>

    <!-- Admin Operations -->
    <div class="sidebar-section">
      <div class="sidebar-title">
        <i data-feather="settings" class="icon"></i> Admin Operations
      </div>
      <form action="logout.php" method="post" onsubmit="return confirm('Are you sure you want to log out?');">
        <button type="submit" class="sidebar-link logout-btn">Logout</button>
      </form>
    </div>

  </div>
</aside>

<!-- Main Content -->
<main class="main-content">
  <?php
  // Include the requested page
  $allowed_pages = ['dashboard', 'createuser', 'units', 'tenants', 'maintenance', 'vacating_notice', 'approved_vacating_notices', 'invoices', 'billing', 'expenses', 'transactions', 'messaging', 'assesmentreport'];
  if (in_array($page, $allowed_pages)) {
      include $page . '.php';
  } else {
      echo "<h1>Page not found</h1>";
  }
  ?>
</main>

<script>
  feather.replace(); // Load feather icons
</script>

</body>
</html>