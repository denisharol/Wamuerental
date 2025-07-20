<?php include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all approved vacating notices
$query = "SELECT tenant_name, house_number, planned_exit_date, created_at FROM approved_vacating_notices ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approved Vacating Notices</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <div class="max-w-6xl mx-auto bg-white p-8 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Approved Vacating Notices</h2>
    <p class="text-sm text-gray-600 mb-6">Below is a list of all approved vacating notices.</p>

    <div class="overflow-x-auto">
      <table class="min-w-full bg-white rounded shadow">
        <thead class="bg-gray-200 text-gray-700">
          <tr>
            <th class="text-left px-4 py-2">Tenant Name</th>
            <th class="text-left px-4 py-2">House Number</th>
            <th class="text-left px-4 py-2">Planned Exit Date</th>
            <th class="text-left px-4 py-2">Approval Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="border-t">
                <td class="px-4 py-2"><?= htmlspecialchars($row['tenant_name']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['house_number']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['planned_exit_date']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($row['created_at']) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center py-4 text-gray-500">No approved vacating notices found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>