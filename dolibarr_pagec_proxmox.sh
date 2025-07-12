#!/bin/bash

# Dolibarr Installation Script for Proxmox VE
# Modified to use Pagecran fork with Pagec branch
# Based on community script but customized for your fork

# Configuration
DOLIBARR_REPO="https://github.com/Pagecran/dolibarr.git"
DOLIBARR_BRANCH="Pagec"
INSTALL_DIR="/var/www/dolibarr"
DB_NAME="dolibarr"
DB_USER="dolibarr"
DB_PASS="Dolibarr2024!"

echo "=== Dolibarr Installation (Pagecran Fork) ==="
echo "Repository: $DOLIBARR_REPO"
echo "Branch: $DOLIBARR_BRANCH"
echo "Date: $(date)"
echo ""

# Check if this is an update or fresh install
if [ -d "$INSTALL_DIR" ] && [ -d "$INSTALL_DIR/.git" ]; then
    echo "Existing installation detected. Performing update..."
    UPDATE_MODE=true
else
    echo "Fresh installation mode."
    UPDATE_MODE=false
fi

# 1. Update system
echo "1. Updating system packages..."
apt update

# 2. Install dependencies (only if fresh install)
if [ "$UPDATE_MODE" = false ]; then
    echo "2. Installing dependencies..."
    apt install -y \
        apache2 \
        mariadb-server \
        php \
        php-mysql \
        php-gd \
        php-xml \
        php-mbstring \
        php-curl \
        php-zip \
        php-intl \
        php-soap \
        php-ldap \
        php-json \
        php-cli \
        git \
        unzip

    # 3. Start and enable services
    echo "3. Starting services..."
    systemctl enable apache2
    systemctl start apache2
    systemctl enable mariadb
    systemctl start mariadb

    # 4. Secure MariaDB
    echo "4. Securing MariaDB..."
    mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "FLUSH PRIVILEGES;"

    # 5. Create Dolibarr database
    echo "5. Creating Dolibarr database..."
    mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
fi

# 6. Backup configuration if updating
if [ "$UPDATE_MODE" = true ]; then
    echo "6. Backing up configuration..."
    if [ -f "$INSTALL_DIR/htdocs/conf/conf.php" ]; then
        cp $INSTALL_DIR/htdocs/conf/conf.php /tmp/dolibarr_conf_backup.php
    fi
    if [ -d "$INSTALL_DIR/documents" ]; then
        tar -czf /tmp/dolibarr_documents_backup.tar.gz -C $INSTALL_DIR documents/
    fi
fi

# 7. Download/Update from Pagecran fork
echo "7. Downloading/Updating from Pagecran fork..."
if [ "$UPDATE_MODE" = true ]; then
    # Update existing installation
    cd $INSTALL_DIR
    git fetch origin
    git reset --hard origin/$DOLIBARR_BRANCH
    git clean -fd
else
    # Fresh installation
    if [ -d "$INSTALL_DIR" ]; then
        echo "Removing old installation..."
        rm -rf $INSTALL_DIR
    fi
    git clone -b $DOLIBARR_BRANCH $DOLIBARR_REPO $INSTALL_DIR
fi

# 8. Backup files are kept for optional restoration via web interface
if [ "$UPDATE_MODE" = true ]; then
    echo "8. Backup files preserved for optional restoration..."
    echo "   - Configuration backup: /tmp/dolibarr_conf_backup.php"
    echo "   - Documents backup: /tmp/dolibarr_documents_backup.tar.gz"
    echo "   - Use the web interface to restore if needed"
fi

# 9. Set permissions
echo "9. Setting permissions..."
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR
chmod -R 777 $INSTALL_DIR/documents
chmod -R 777 $INSTALL_DIR/htdocs/conf

# 10. Configure Apache (only if fresh install)
if [ "$UPDATE_MODE" = false ]; then
    echo "10. Configuring Apache..."
    tee /etc/apache2/sites-available/dolibarr.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $INSTALL_DIR/htdocs
    
    <Directory $INSTALL_DIR/htdocs>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/dolibarr_error.log
    CustomLog \${APACHE_LOG_DIR}/dolibarr_access.log combined
</VirtualHost>
EOF

    # 11. Enable Dolibarr site
    echo "11. Enabling Dolibarr site..."
    a2dissite 000-default
    a2ensite dolibarr
    a2enmod rewrite
fi

# 12. Restart Apache
echo "12. Restarting Apache..."
systemctl restart apache2

# 13. Final information
echo ""
if [ "$UPDATE_MODE" = true ]; then
    echo "=== UPDATE COMPLETED ==="
else
    echo "=== INSTALLATION COMPLETED ==="
fi
echo "Repository used: $DOLIBARR_REPO"
echo "Branch: $DOLIBARR_BRANCH"
echo "Database: $DB_NAME"
echo "Database user: $DB_USER"
echo "Database password: $DB_PASS"
echo "Access URL: http://$(hostname -I | awk '{print $1}')/"

if [ "$UPDATE_MODE" = false ]; then
    echo "Installation URL: http://$(hostname -I | awk '{print $1}')/install/"
    echo ""
    echo "To complete installation:"
    echo "1. Open http://$(hostname -I | awk '{print $1}')/install/"
    echo "2. Follow the installation wizard"
    echo "3. Use the database information above"
fi

echo ""
echo "Installation/Update from Pagecran fork completed successfully!"
echo ""
echo "To update in the future, simply run this script again."