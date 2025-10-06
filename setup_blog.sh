#!/bin/bash
# 🚀 PHP Blog Auto-Setup Script for Kali Linux

echo "==============================="
echo "  📰 PHP Blog Project Setup"
echo "==============================="

# Update packages
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

# Install PHP and extensions
sudo apt install php libapache2-mod-php php-mysql php-cli -y

# Install MySQL
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo systemctl start mysql

# Secure MySQL
sudo mysql_secure_installation <<EOF
y
n
y
y
y
EOF

# Create DB
read -p "Enter database name [blogdb]: " DB_NAME
DB_NAME=${DB_NAME:-blogdb}
read -p "Enter MySQL root password you set earlier: " MYSQL_ROOT_PASS
mysql -u root -p$MYSQL_ROOT_PASS -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

# Copy Project
read -p "Enter full path of your project folder: " PROJECT_PATH
read -p "Enter target folder name in /var/www/html [myblog]: " TARGET_FOLDER
TARGET_FOLDER=${TARGET_FOLDER:-myblog}
sudo cp -r $PROJECT_PATH /var/www/html/$TARGET_FOLDER
sudo chown -R www-data:www-data /var/www/html/$TARGET_FOLDER
sudo chmod -R 755 /var/www/html/$TARGET_FOLDER

# Optional SQL Import
read -p "Do you want to import a .sql file? (y/n): " IMPORT_SQL
if [[ $IMPORT_SQL == "y" ]]; then
    read -p "Enter full path of your SQL file: " SQL_PATH
    mysql -u root -p$MYSQL_ROOT_PASS $DB_NAME < $SQL_PATH
fi

# Restart Apache
sudo systemctl restart apache2

echo "✅ Setup Complete!"
echo "🌐 Visit: http://localhost/$TARGET_FOLDER"
