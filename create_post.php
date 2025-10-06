<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $content = trim($_POST['content']); 
    $author_id = $_SESSION['user_id'];

    $image_url = "";
    $media_url = "";

    // File upload
    if (!empty($_FILES['media']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES['media']['name']);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_url = $target_file;
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
                $image_url = $target_file;
            }
        }
    }

    if (!empty($title) && !empty($description) && !empty($category) && !empty($content)) {
        $status = ($_POST['action'] === "draft") ? "draft" : "published";
        $sql = "INSERT INTO posts (title, description, category, content, image_url, media_url, author_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssis", $title, $description, $category, $content, $image_url, $media_url, $author_id, $status);
        if ($stmt->execute()) {
            $_SESSION['msg'] = ($status === "draft") ? "📝 Draft saved successfully!" : "✅ Post published successfully!";
            header("Location: user_profile.php");
            exit;
        } else {
            $error = "❌ Error creating post.";
        }
    } else {
        $error = "⚠️ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Create Post - Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

<!-- 🔹 Dark theme overrides for EasyMDE -->
<style>
/* Editor background */
.CodeMirror, .editor-toolbar {
  background-color: #0f172a !important; /* slate-900 */
  color: #e2e8f0 !important; /* text-slate-200 */
}

/* Toolbar buttons */
.editor-toolbar button {
  color: #e2e8f0 !important;
  border: none !important;
}
.editor-toolbar button:hover {
  background: #1e293b !important; /* slate-800 */
  color: #38bdf8 !important; /* cyan highlight */
}

/* Editor content */
.CodeMirror-lines {
  color: #e2e8f0 !important;
}
.CodeMirror-cursor {
  border-left: 2px solid #38bdf8 !important; /* cyan cursor */
}
</style>
</head>

<body class="min-h-screen font-display text-slate-200 bg-gradient-to-br from-slate-900 via-slate-950 to-black">
<div class="flex flex-col min-h-screen">

<!-- Navbar -->
<header class="border-b border-slate-800">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48">
        <path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/>
      </svg>
      <span class="text-xl font-bold text-white">Hackers Gurukul Blog</span>
    </a>
    <nav class="hidden md:flex gap-8">
      <a href="homepage.php" class="hover:text-primary">Home</a>
      <a href="create_post.php" class="hover:text-primary font-bold">Create Post</a>
      <a href="contactpage.php" class="hover:text-primary">Contact</a>
    </nav>
    <div class="flex items-center gap-4">
      <img src="https://i.pravatar.cc/40" alt="Profile" class="h-9 w-9 rounded-full"/>
    </div>
  </div>
</header>

<!-- Create Post Section -->
<main class="flex-grow container mx-auto px-4 py-10 max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-10">

  <!-- Post Form -->
  <div class="bg-slate-900 p-6 rounded-2xl shadow-xl space-y-5 transition hover:scale-[1.01] hover:shadow-2xl">
    <h1 class="text-2xl font-bold">✍️ Create a New Post</h1>

    <?php if(!empty($error)): ?>
      <p class="text-red-500 mb-4"><?php echo $error; ?></p>
    <?php endif; ?>

    <form id="postForm" action="" method="POST" enctype="multipart/form-data" class="space-y-5">

      <div>
        <label class="block text-sm font-medium">Title</label>
        <input type="text" name="title" id="title" required 
               class="w-full px-4 py-2 border border-slate-700 rounded-lg bg-slate-800 text-white focus:ring-primary"/>
      </div>

      <div>
        <label class="block text-sm font-medium">Short Description</label>
        <input type="text" name="description" id="description" required 
               class="w-full px-4 py-2 border border-slate-700 rounded-lg bg-slate-800 text-white focus:ring-primary"/>
      </div>

      <div>
        <label class="block text-sm font-medium">Category</label>
        <select name="category" id="category" required 
                class="w-full px-4 py-2 border border-slate-700 rounded-lg bg-slate-800 text-white focus:ring-primary">
          <option value="">-- Select Category --</option>
          <option value="AI">AI</option>
          <option value="Software">Software</option>
          <option value="Hardware">Hardware</option>
          <option value="Cybersecurity">Cybersecurity</option>
          <option value="Reviews">Reviews</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium">Full Content (Markdown Supported)</label>
        <textarea name="content" id="content" rows="10"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium">Upload Image / File / Video</label>
        <input type="file" name="media" id="media" accept="image/*,video/*,.pdf,.doc,.docx" 
               class="w-full text-sm text-slate-300" onchange="previewFile(event)"/>
      </div>

      <div class="flex gap-3">
        <button type="submit" name="action" value="publish" 
                class="w-full py-2.5 px-4 rounded-lg text-sm font-bold text-white bg-primary hover:bg-primary/90">
          🚀 Publish
        </button>
        <button type="submit" name="action" value="draft" 
                class="w-full py-2.5 px-4 rounded-lg text-sm font-bold text-white bg-slate-500 hover:bg-slate-600">
          📝 Save as Draft
        </button>
      </div>
    </form>
  </div>

  <!-- Live Preview -->
  <div class="bg-slate-900 p-6 rounded-2xl shadow-xl space-y-4 transition hover:scale-[1.01] hover:shadow-2xl">
    <h2 class="text-2xl font-bold">🔍 Live Preview</h2>
    <div id="previewCard" class="space-y-3">
      <div id="previewMedia"></div>
      <h3 class="text-2xl font-bold" id="previewTitle">Your title will appear here</h3>
      <p class="text-sm text-gray-400" id="previewCategory">Category: None</p>
      <p class="text-gray-300" id="previewDescription">Your description will appear here</p>
      <div id="previewContent" class="prose dark:prose-invert">Your content will appear here...</div>
    </div>
  </div>

</main>

<?php include 'footer.php'; ?>
</div>

<!-- JS -->
<script>
let easyMDE = new EasyMDE({
  element: document.getElementById("content"),
  spellChecker: false,
  autosave: { enabled: false },
  placeholder: "Write your blog content in Markdown...",
  status: false,
  toolbar: ["bold","italic","heading","|","unordered-list","ordered-list","|","link","image","code","quote","preview","guide"]
});

function updatePreview() {
  document.getElementById("previewTitle").innerText = document.getElementById("title").value || "Your title will appear here";
  document.getElementById("previewDescription").innerText = document.getElementById("description").value || "Your description will appear here";
  let cat = document.getElementById("category").value;
  document.getElementById("previewCategory").innerText = cat ? "Category: " + cat : "Category: None";
  document.getElementById("previewContent").innerHTML = easyMDE.markdown(easyMDE.value()) || "Your content will appear here...";
}

["title","description","category"].forEach(id=>{
  document.getElementById(id).addEventListener("input", updatePreview);
});
easyMDE.codemirror.on("change", updatePreview);

function previewFile(event) {
  const file = event.target.files[0];
  const preview = document.getElementById("previewMedia");
  preview.innerHTML = "";
  if(file){
    const ext = file.name.split('.').pop().toLowerCase();
    if(["jpg","jpeg","png","gif","webp"].includes(ext)){
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" class="w-full rounded-lg"/>`; };
      reader.readAsDataURL(file);
    } else if(["mp4","webm","ogg"].includes(ext)){
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<video controls class="w-full rounded-lg"><source src="${e.target.result}" type="video/${ext}"></video>`; };
      reader.readAsDataURL(file);
    } else {
      preview.innerHTML = `<p class="text-primary">📂 File ready to upload: ${file.name}</p>`;
    }
  }
}
</script>
</body>
</html>
