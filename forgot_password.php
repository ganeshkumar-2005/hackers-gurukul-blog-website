<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $error = "❌ New password and Confirm password do not match!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? AND email=? LIMIT 1");
        $stmt->bind_param("ss", $name, $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();
            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param("si", $new_password, $user['id']);
            if ($update->execute()) {
                $success = "✅ Password updated successfully! Redirecting to login...";
                header("refresh:2;url=loginpage.php");
            } else {
                $error = "❌ Failed to update password. Try again.";
            }
            $update->close();
        } else {
            $error = "⚠️ No matching account found for that name and email.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Forgot Password - Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: { primary: "#1193d4", "background-dark": "#101c22" },
      fontFamily: { display: ["Newsreader"] }
    },
  },
};
</script>
</head>
<body class="bg-background-dark text-slate-200 font-display">
<div class="flex flex-col min-h-screen">

<!-- 🔹 Navbar -->
<header class="border-b border-slate-800 bg-slate-900">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" viewBox="0 0 48 48"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
      <span class="text-xl font-bold">Hackers Gurukul Blog</span>
    </a>
    <a href="loginpage.php" class="px-4 py-2 bg-primary rounded-lg text-white">Login</a>
  </div>
</header>

<!-- 🔹 Forgot Password Form -->
<main class="flex-grow flex items-center justify-center px-4">
  <div class="w-full max-w-md bg-slate-900 p-8 rounded-xl shadow border border-slate-800">
    <h2 class="text-2xl font-bold text-center mb-6">🔑 Forgot Password</h2>
    <p class="text-center text-sm text-slate-400 mb-4">Verify your name and email to set a new password.</p>

    <?php if($success): ?>
      <div class="bg-green-600/20 border border-green-500 text-green-300 p-3 rounded mb-4 text-sm">
        <?php echo $success; ?>
      </div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="bg-red-600/20 border border-red-500 text-red-300 p-3 rounded mb-4 text-sm">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Name</label>
        <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white"/>
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" required class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white"/>
      </div>
      <div>
        <label class="block text-sm font-medium">New Password</label>
        <input type="password" name="new_password" required class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white"/>
      </div>
      <div>
        <label class="block text-sm font-medium">Confirm New Password</label>
        <input type="password" name="confirm_password" required class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white"/>
      </div>
      <button type="submit" class="w-full bg-primary py-2 rounded-lg text-white font-bold hover:bg-primary/80">
        Reset Password
      </button>
    </form>
  </div>
</main>

<?php include 'footer.php'; ?>
</div>
</body>
</html>
