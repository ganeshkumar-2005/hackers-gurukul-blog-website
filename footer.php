<?php
// Fetch categories dynamically for footer
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$categories = $conn->query("SELECT DISTINCT category FROM posts ORDER BY category ASC");
?>
<!-- 🔹 Footer -->
<footer class="bg-slate-900 text-slate-300 mt-12">
  <div class="container mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">

    <!-- Brand -->
    <div>
      <a href="homepage.php" class="flex items-center gap-2 mb-4">
        <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path>
        </svg>
        <span class="text-lg font-bold text-white">Hackers Gurukul</span>
      </a>
      <p class="text-sm text-slate-400">
        Your source for the latest in technology, AI, and cybersecurity.
      </p>
    </div>

    <!-- Links -->
    <div>
      <h3 class="text-white font-bold mb-3">Links</h3>
      <ul class="space-y-2">
        <li><a href="about.php" class="hover:text-primary">About Us</a></li>
        <li><a href="contactpage.php" class="hover:text-primary">Contact</a></li>
        <li><a href="privacy-policy.php" class="hover:text-primary">Privacy Policy</a></li>
        <li><a href="terms.php" class="hover:text-primary">Terms & Conditions</a></li>
      </ul>
    </div>

    <!-- Categories -->
    <div>
      <h3 class="text-white font-bold mb-3">Categories</h3>
      <ul class="space-y-2">
        <?php if ($categories && $categories->num_rows): ?>
          <?php while($cat = $categories->fetch_assoc()): ?>
            <li>
              <a href="categories.php?category=<?php echo urlencode($cat['category']); ?>" class="hover:text-primary">
                <?php echo htmlspecialchars($cat['category']); ?>
              </a>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li class="text-slate-500 text-sm">No categories yet</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Social -->
    <div>
      <h3 class="text-white font-bold mb-3">Follow Us</h3>
      <div class="flex space-x-4">
        <!-- Twitter -->
        <a href="#" class="hover:text-primary" aria-label="Twitter">
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M22.46 6c-.77.35-1.5.58-2.3.69a3.94 3.94 0 0 0 1.7-2.16c-.8.48-1.64.83-2.56 1.01a3.92 3.92 0 0 0-6.76 3.58A11.16 11.16 0 0 1 3.16 4.7a3.9 3.9 0 0 0 1.21 5.22c-.67-.02-1.3-.2-1.85-.5v.05c0 1.9 1.35 3.5 3.16 3.86a3.93 3.93 0 0 1-1.77.07 3.93 3.93 0 0 0 3.66 2.72A7.87 7.87 0 0 1 2 18.57a11.13 11.13 0 0 0 6.04 1.77c7.25 0 11.22-6 11.22-11.2v-.51A7.9 7.9 0 0 0 22.46 6z"/>
          </svg>
        </a>
        <!-- Facebook -->
        <a href="#" class="hover:text-primary" aria-label="Facebook">
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 5 3.66 9.13 8.44 9.88v-7H8v-2.88h2.44V9.5c0-2.4 1.43-3.72 3.6-3.72 1.05 0 2.15.19 2.15.19v2.38h-1.21c-1.2 0-1.57.74-1.57 1.5v1.79H16.7L16.3 15h-2.88v7C18.34 21.13 22 17 22 12z"/>
          </svg>
        </a>
        <!-- Instagram -->
        <a href="#" class="hover:text-primary" aria-label="Instagram">
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2.16c3.2 0 3.584.012 4.85.07 1.17.056 1.97.24 2.43.403a4.9 4.9 0 0 1 1.77 1.14 4.9 4.9 0 0 1 1.14 1.77c.163.46.347 1.26.403 2.43.058 1.27.07 1.65.07 4.85s-.012 3.584-.07 4.85c-.056 1.17-.24 1.97-.403 2.43a4.9 4.9 0 0 1-1.14 1.77 4.9 4.9 0 0 1-1.77 1.14c-.46.163-1.26.347-2.43.403-1.27.058-1.65.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.056-1.97-.24-2.43-.403a4.9 4.9 0 0 1-1.77-1.14 4.9 4.9 0 0 1-1.14-1.77c-.163-.46-.347-1.26-.403-2.43C2.172 15.584 2.16 15.2 2.16 12s.012-3.584.07-4.85c.056-1.17.24-1.97.403-2.43a4.9 4.9 0 0 1 1.14-1.77 4.9 4.9 0 0 1 1.77-1.14c.46-.163 1.26-.347 2.43-.403C8.416 2.172 8.8 2.16 12 2.16z"/>
          </svg>
        </a>
      </div>
    </div>
  </div>

  <!-- Bottom -->
  <div class="border-t border-slate-800 mt-6 pt-6 text-center text-sm text-slate-500">
    © 2025 Hackers Gurukul Blog. All rights reserved.
  </div>
</footer>
