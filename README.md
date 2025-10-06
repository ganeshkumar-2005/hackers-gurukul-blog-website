
# 📰 Dynamic Blog & CMS Platform

A **full-featured dynamic blog and content management system** built with **PHP, MySQL, HTML, CSS, and JavaScript**.  
It includes a responsive front end for users and a secure admin panel for managing posts, categories, users, and pages.  
Perfect for personal blogs, portfolio sites, learning projects, or college submissions.

---

## ✨ Features

### 👥 User Features
- 🏠 Homepage with recent posts and category sidebar.  
- 📝 Post detail view with like & view tracking.  
- ❤️ Like system using PHP and MySQL.  
- 👤 User signup, login, logout, and profile management.  
- 🔑 Forgot and reset password functionality.  
- 📬 Contact page for user queries.  
- 📄 Static pages — About, Terms, Privacy Policy, Welcome.

### 🔐 Admin Features
- 📝 Create, edit, and delete posts.  
- 🗂️ Manage categories and pages.  
- 👥 Manage users.  
- 📊 View post analytics (likes/views).  
- ⚡ Modular structure for easy maintenance.

---

## 🛠️ Tech Stack

| Layer       | Technology Used                  |
|------------|-----------------------------------|
| Frontend   | HTML, CSS, JavaScript            |
| Backend    | PHP (Core PHP)                   |
| Database   | MySQL                            |
| Auth       | Custom PHP Authentication        |
| Server     | Apache2                          |
| OS         | Kali Linux / Debian / Ubuntu    |

---

## 🚀 Manual Installation (Kali Linux)

Follow these commands step by step in your terminal:

bash
# 1️⃣ Update packages
sudo apt update && sudo apt upgrade -y

# 2️⃣ Install Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# 3️⃣ Install PHP & extensions
sudo apt install php libapache2-mod-php php-mysql -y

# 4️⃣ Install MySQL Server
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql
sudo mysql_secure_installation

# 5️⃣ Create Database
sudo mysql -u root -p
CREATE DATABASE blogdb;

# 6️⃣ Move project to web directory
sudo cp -r ~/Desktop/myblog /var/www/html/
sudo chown -R www-data:www-data /var/www/html/myblog
sudo chmod -R 755 /var/www/html/myblog

# 7️⃣ Import SQL (if you have)
mysql -u root -p blogdb < ~/Desktop/blog.sql

# 8️⃣ Restart Apache
sudo systemctl restart apache2

# 🌐 Open in browser:
# http://localhost/myblog/
# To Excute:
chmod +x setup_blog.sh
# Run It:
./setup_blog.sh



