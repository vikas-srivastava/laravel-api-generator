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
echo "Copying example-docker-compose.yml from local folder..."
if [[ ! -f "../example-docker-compose.yml" ]]; then
    error_exit "docker-compose.yml not found in the parent directory"
fi
cp ../example-docker-compose.yml docker-compose.yml || error_exit "Failed to copy docker-compose.yml"


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

# Optimize Composer
echo "Optimizing Composer..."
./vendor/bin/sail composer dump-autoload || error_exit "Failed to dump autoload"
./vendor/bin/sail artisan optimize || error_exit "Failed to optimize Composer"

echo "Setup complete! Don't forget to manually review and adjust the generated files as needed."