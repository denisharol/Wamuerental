<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$dbname = "Demo1";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$loginMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];

    // Check if the phone number exists in the caretaker table
    $stmt = $conn->prepare("SELECT id, password FROM caretaker WHERE phone = ?");
    if ($stmt) {
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($caretaker_id, $hashed_password);
            $stmt->fetch();

            // Verify the password
            if (password_verify($pass, $hashed_password)) {
                // Set session variables
                $_SESSION['caretaker_id'] = $caretaker_id;
                $_SESSION['phone'] = $phone;

                // Redirect to the dashboard
                header("Location: /Demo/caretaker/index.php");
                exit;
            } else {
                $loginMessage = "<p class='text-red-600'>Incorrect password.</p>";
            }
        } else {
            $loginMessage = "<p class='text-red-600'>Phone number not found.</p>";
        }
        $stmt->close();
    } else {
        $loginMessage = "<p class='text-red-600'>Error preparing statement.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Caretaker Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center h-screen bg-gray-100">

  <form action="" method="POST" class="bg-white p-8 rounded shadow-md w-full max-w-sm space-y-4">
    <h2 class="text-2xl font-bold text-center">Caretaker Login</h2>

    <?php if ($loginMessage) echo "<div class='text-center'>$loginMessage</div>"; ?>

    <div>
      <label class="block mb-1 text-sm font-medium text-gray-700">Phone Number</label>
      <div class="flex items-center border rounded-lg overflow-hidden">
        <span class="bg-gray-200 px-3 py-2 text-gray-600">(+254)0</span>
        <input type="text" name="phone" maxlength="9" required class="flex-1 px-4 py-2 focus:outline-none">
      </div>
    </div>

    <div class="relative">
      <label class="block mb-1 text-sm font-medium text-gray-700">Password</label>
      <input type="password" name="password" id="password" required class="w-full px-4 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-400">
      <svg id="togglePassword" xmlns="http://www.w3.org/2000/svg" class="absolute right-3 top-9 h-5 w-5 text-gray-500 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
    </div>

    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">Login</button>
  </form>

  <script>
    const toggle = document.getElementById("togglePassword");
    const password = document.getElementById("password");

    toggle.addEventListener("click", () => {
      const type = password.getAttribute("type") === "password" ? "text" : "password";
      password.setAttribute("type", type);
      toggle.classList.toggle("text-blue-600");
    });
  </script>
</body>
</html>