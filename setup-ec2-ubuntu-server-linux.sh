#!/bin/bash

# Update and install necessary packages
echo "Updating and installing necessary packages..."
sudo apt update -y
sudo apt upgrade -y
sudo apt install -y php php-pear php-cgi php-common php-curl php-mbstring php-gd php-gettext php-bcmath php-json php-xml php-fpm php-intl php-zip php-pgsql

# Install Composer
echo "Installing Composer..."
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Install PostgreSQL
echo "Installing PostgreSQL..."
sudo apt install -y postgresql postgresql-contrib

# Initialize PostgreSQL (Ubuntu handles initialization automatically)
echo "Ensuring PostgreSQL is started and enabled..."
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Check PostgreSQL status
sudo systemctl status postgresql

# Set up PostgreSQL database and user if needed
# Uncomment and modify these lines according to your specific requirements
# echo "Creating PostgreSQL database and user..."
# sudo -u postgres psql -c "CREATE DATABASE your_database;"
# sudo -u postgres psql -c "CREATE USER your_user WITH ENCRYPTED PASSWORD 'your_password';"
# sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE your_database TO your_user;"

# Install Nginx
echo "Installing Nginx..."
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Install Git
echo "Installing Git..."
sudo apt install -y git

# Set up project directory
echo "Setting up project directory..."
sudo mkdir -p /var/www/laravel
sudo chown ubuntu:ubuntu /var/www/laravel

# Configure Nginx
echo "Configuring Nginx..."
sudo tee /etc/nginx/sites-available/laravel <<EOT
server {
    listen 80;
    server_name your_domain.com;
    root /var/www/laravel/current/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOT

# Enable the new Nginx configuration
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo systemctl restart nginx

# Install Deployer
echo "Installing Deployer..."
curl -LO https://deployer.org/deployer.phar
sudo mv deployer.phar /usr/local/bin/dep
sudo chmod +x /usr/local/bin/dep

# Install the AWS Systems Manager Agent (SSM Agent)
# echo "Installing AWS Systems Manager Agent (SSM Agent)..."
# wget https://s3.amazonaws.com/ec2-downloads-windows/SSMAgent/latest/ubuntu_amd64/amazon-ssm-agent.deb
# sudo dpkg -i amazon-ssm-agent.deb
# sudo systemctl enable amazon-ssm-agent
# sudo systemctl start amazon-ssm-agent

echo "EC2 instance setup complete!"