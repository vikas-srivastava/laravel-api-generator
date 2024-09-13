# SHELL SCRIPT IN ACTION

```bash
#!/bin/bash

# Function to display error messages and exit
error_exit() {
    echo "Error: $1" >&2
    exit 1
}

# Ask for the folder name to install Laravel
read -p "Enter the folder name to install Laravel: " folder_name

# Install Latest Laravel
echo "Installing Latest Laravel into $folder_name..."
curl "https://laravel.build/${folder_name}" | bash || error_exit "Failed to install Laravel"

# Navigate to the project directory
cd "$folder_name" || error_exit "Failed to navigate to project directory"

# Detect OS
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed_inplace='sed -i ""'
else
    # Linux
    sed_inplace='sed -i'
fi

# Copy docker-compose.yml from local folder
echo "Copying docker-compose.yml from local folder..."
if [[ ! -f "../docker-compose.yml" ]]; then
    error_exit "docker-compose.yml not found in the parent directory"
fi
cp ../docker-compose.yml docker-compose.yml || error_exit "Failed to copy docker-compose.yml"


# Update .env file
echo "Updating .env file..."
$sed_inplace 's/DB_CONNECTION=mysql/DB_CONNECTION=pgsql/g' .env
$sed_inplace 's/DB_HOST=mysql/DB_HOST=postgres/g' .env
$sed_inplace 's/DB_PORT=3306/DB_PORT=5432/g' .env
$sed_inplace "s/DB_DATABASE=laravel/DB_DATABASE=${folder_name}/g" .env

# Rebuild Sail
echo "Rebuilding Sail with progress..."
./vendor/bin/sail build --no-cache --progress=plain || error_exit "Failed to rebuild Sail"

# Start Sail
echo "Starting Sail..."
./vendor/bin/sail up -d || error_exit "Failed to start Sail"

# Create the database
DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f 2)
if [[ -z "$DB_DATABASE" ]]; then
    error_exit "Database name is not set in the .env file"
fi
echo "Creating the database $DB_DATABASE..."
./vendor/bin/sail exec postgres psql -U sail -d postgres -c "CREATE DATABASE \"$DB_DATABASE\";" || error_exit "Failed to create database"


# Run migrations
echo "Running migrations..."
./vendor/bin/sail artisan migrate || error_exit "Failed to run migrations"

# Setup Jetstream
echo "Setting up Jetstream..."
./vendor/bin/sail composer require laravel/jetstream || error_exit "Failed to install Jetstream"
./vendor/bin/sail artisan jetstream:install inertia --teams || error_exit "Failed to install Jetstream with Inertia and teams"
./vendor/bin/sail npm install || error_exit "Failed to install npm dependencies"
./vendor/bin/sail npm run build || error_exit "Failed to build assets"
./vendor/bin/sail artisan migrate || error_exit "Failed to run Jetstream migrations"
./vendor/bin/sail artisan install:api || error_exit "Failed to install API"

# Setup Scramble for API Documentation
echo "Setting up Scramble..."
./vendor/bin/sail composer require dedoc/scramble || error_exit "Failed to install Scramble"

# Install Laravel Tools
echo "Installing Laravel Tools..."
echo "Please manually download and place the required files in App/console/Commands folder"

# Generate Default Database Schema
echo "Generating Default Database Schema..."
./vendor/bin/sail artisan make:migration create_bussiness_specific_actors_use_cases_schema || error_exit "Failed to create migration"
./vendor/bin/sail artisan migrate || error_exit "Failed to run migration"

# Install Laravel Octane (Optional)
read -p "Do you want to install Laravel Octane? (y/n) " install_octane
if [[ $install_octane =~ ^[Yy]$ ]]; then
    echo "Installing Laravel Octane..."
    ./vendor/bin/sail composer require laravel/octane || error_exit "Failed to install Laravel Octane"
fi

# Add Private Git Repository (Laravel API Generator)
# echo "Adding private Git repository for Laravel API Generator..."
# ./vendor/bin/sail composer config repositories.laravel-api-generator vcs git@github.com:vikas-srivastava/laravel-api-generator.git || error_exit "Failed to add private repository"
# ./vendor/bin/sail composer require vikas-srivastava/laravel-api-generator || error_exit "Failed to install Laravel API Generator"

# ./vendor/bin/sail artisan vendor:publish --provider="vikas-srivastava\laravel-api-generator\ApiGeneratorServiceProvider" --tag="config"

# Setup Base API - Edit cms_modules as per your Bussiness Context
# echo "Setting up Base API Models based upon cms_modules.php"
# ./vendor/bin/sail artisan vcapi:generate || error_exit "Failed to create base API models"
# ./vendor/bin/sail artisan migrate || error_exit "Failed to run migrations after generating API models"

# Optimize Composer
echo "Optimizing Composer..."
./vendor/bin/sail composer dump-autoload || error_exit "Failed to dump autoload"
./vendor/bin/sail composer optimize || error_exit "Failed to optimize Composer"

echo "Setup complete! Don't forget to manually review and adjust the generated files as needed."
```

# HOW TO USE THIS REPO

Make sure to free the Laravel related docker port by stopping other local php83 docker instances
./install-custom-api.sh
copy and edit cms_module
sail artisan vcapi:generate
sail artisan migrate
visit localhost:8080
