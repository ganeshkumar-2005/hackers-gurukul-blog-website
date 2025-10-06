<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$success_msg = $error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "⚠️ Please enter a valid email address.";
    } else {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($user = $res->fetch_assoc()) {
            $user_id = $user['id'];
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

            // Store token in DB (create password_resets table if not exists)
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(100) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)");
            $stmt2->bind_param("iss", $user_id, $token, $expires);
            $stmt2->execute();
            $stmt2->close();

            // 📌 Later: send the reset link via email. For now, show link.
            $reset_link = "http://localhost/hackers_gurukul_blog/reset_password.php?token=" . urlencode($token);
            $success_msg = "✅ A password reset link has been generated.<br>
                            <span class='text-xs text-gray-400'>For testing, click <a href='$reset_link' class='text-primary underline'>here</a></span>";
        } else {
            $error_msg = "❌ No account found with this email.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - Hackers Gurukul Blog</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: { primary: "#1193d4", "background-dark": "#101c22" },
          fontFamily: { display: ["Space Grotesk"] },
        },
      },
    };
  </script>
</head>
<body class="bg-background-dark text-white font-display min-h-screen flex flex-col">
<!-- 🔹 Navbar -->
<header class="bg-transparent absolute top-0 left-0 right-0 z-20 px-6 py-4 flex justify-between items-center">
  <a href="homepage.php" class="flex items-center gap-2">
    <svg class="h-7 w-7 text-primary" viewBox="0 0 48 48" fill="none">
      <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/>
    </svg>
    <span class="text-lg font-bold">Hackers Gurukul Blog</span>
  </a>
  <nav class="hidden md:flex gap-6">
    <a href="homepage.php" class="hover:text-primary">Home</a>
    <a href="categories.php" class="hover:text-primary">Categories</a>
    <a href="contactpage.php" class="hover:text-primary">Contact</a>
  </nav>
</header>

<!-- 🔹 Forgot Password Form -->
<main class="flex flex-1 justify-center items-center px-4">
  <div class="bg-slate-900/80 backdrop-blur-md p-8 rounded-2xl shadow-xl max-w-md w-full mt-20">
    <h1 class="text-2xl font-bold mb-4 text-center text-primary">Forgot Password</h1>
    <p class="text-sm text-gray-400 text-center mb-6">Enter your registered email address. We'll send you a link to reset your password.</p>

    <?php if ($success_msg): ?>
      <div class="bg-green-600/20 text-green-300 p-3 rounded mb-4 text-sm text-center"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="bg-red-600/20 text-red-300 p-3 rounded mb-4 text-sm text-center"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label for="email" class="block text-sm mb-1">Email Address</label>
        <input type="email" name="email" id="email" required class="w-full px-3 py-2 rounded bg-slate-800 text-white focus:ring-2 focus:ring-primary">
      </div>
      <button type="submit" class="w-full bg-primary hover:bg-primary/80 transition text-white py-2 rounded-lg font-semibold">
        Send Reset Link
      </button>
    </form>

    <p class="text-sm text-gray-400 text-center mt-4">
      Remember your password? <a href="loginpage.php" class="text-primary hover:underline">Back to Login</a>
    </p>
  </div>
</main>

<?php include 'footer.php'; ?>
</body>
</html>
