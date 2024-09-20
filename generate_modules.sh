#!/bin/bash

set -e

LOG_FILE="script_logs.log"

usage() {
    echo "Usage: $0 <config_file>"
    exit 1
}

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') | $1" | tee -a "$LOG_FILE"
}

error_exit() {
    echo "Error: $1" | tee -a "$LOG_FILE" >&2
    exit 1
}

trap 'error_exit "An unexpected error occurred."' ERR

check_dependencies() {
    local dependencies=("jq" "php" "composer")
    for cmd in "${dependencies[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            error_exit "Required command '$cmd' is not installed or not in PATH."
        fi
    done
}

capitalize() {
    local word="$1"
    echo "$word" | sed 's/^\(.\)/\U\1/'
}

lowercase() {
    local word="$1"
    echo "$word" | tr '[:upper:]' '[:lower:]'
}

pluralize() {
    local singular="$1"
    local plural=""

    if [[ "$singular" =~ [yY]$ ]]; then
        plural=$(echo "$singular" | sed 's/y$/ies/' | sed 's/Y$/IES/')
    elif [[ "$singular" =~ (s|x|z|ch|sh)$ ]]; then
        plural="${singular}es"
    else
        plural="${singular}s"
    fi

    echo "$plural"
}

# Function to resolve Laravel Sail or Artisan command based on presence of Sail
resolve_artisan_command() {
    local laravel_root="$1"

    if [ -f "$laravel_root/vendor/bin/sail" ]; then
        echo "$laravel_root/vendor/bin/sail artisan"
    elif [ -f "$laravel_root/artisan" ]; then
        echo "php $laravel_root/artisan"
    else
        error_exit "No valid artisan command found in Laravel root '$laravel_root'. Ensure Sail or artisan is installed."
    fi
}

generate_module() {
    local module="$1"
    local artisan_cmd="$2"

    log "Creating module: $module"

    # Create the module
    $artisan_cmd module:make "$module" || error_exit "Failed to create module '$module'."

    # After the module is created, update autoloader to recognize new service provider
    log "Running composer dump-autoload after creating the module..."
    composer dump-autoload || error_exit "Failed to run composer dump-autoload after creating module '$module'."

    log "Module '$module' created successfully."
}

generate_model() {
    local module="$1"
    local class="$2"
    local fields="$3"
    local artisan_cmd="$4"

    log "Generating Model for $class in module $module..."

    $artisan_cmd module:make-model "$class" "$module" || error_exit "Failed to generate model for $class in module '$module'."

    log "Generated Model for $class in module '$module'."
}

generate_controller() {
    local module="$1"
    local class="$2"
    local artisan_cmd="$3"

    log "Generating Controller for $class in module $module..."

    $artisan_cmd module:make-controller "${class}Controller" "$module" --resource --model="$class" || error_exit "Failed to generate controller for $class in module '$module'."

    log "Generated Controller for $class in module '$module'."
}

generate_migration() {
    local module="$1"
    local class="$2"
    local fields="$3"
    local artisan_cmd="$4"

    local table_name
    table_name="$(pluralize "$(lowercase "$class")")"

    log "Generating Migration for $class in module $module..."

    $artisan_cmd module:make-migration "create_${table_name}_table" "$module" --create="$table_name" || error_exit "Failed to generate migration for $class in module '$module'."

    log "Generated Migration for $class in module '$module'."
}

generate_factory() {
    local module="$1"
    local class="$2"
    local fields="$3"
    local artisan_cmd="$4"

    log "Generating Factory for $class in module $module..."

    $artisan_cmd module:make-factory "$class" "$module" || error_exit "Failed to generate factory for $class in module '$module'."

    log "Generated Factory for $class in module '$module'."
}

generate_resource() {
    local module="$1"
    local class="$2"
    local artisan_cmd="$3"

    log "Generating API Resource for $class in module $module..."

    $artisan_cmd module:make-resource "${class}Resource" "$module" || error_exit "Failed to generate API Resource for $class in module '$module'."

    log "Generated API Resource for $class in module '$module'."
}

generate_form_request() {
    local module="$1"
    local class="$2"
    local fields="$3"
    local artisan_cmd="$4"

    log "Generating Form Request for $class in module $module..."

    $artisan_cmd module:make-request "${class}Request" "$module" || error_exit "Failed to generate Form Request for $class in module '$module'."

    log "Generated Form Request for $class in module '$module'."
}

generate_test() {
    local module="$1"
    local class="$2"
    local features="$3"
    local artisan_cmd="$4"

    log "Generating Test for $class in module $module..."

    $artisan_cmd module:make-test "${class}Test" "$module" --unit || error_exit "Failed to generate Test for $class in module '$module'."

    log "Generated Test for $class in module '$module'."
}

generate_routes() {
    local module="$1"
    local class="$2"
    local artisan_cmd="$3"

    log "Generating Routes for $class in module $module..."

    $artisan_cmd module:make-route "api" "$module" || error_exit "Failed to generate API routes for module '$module'."

    log "Generated Routes for module '$module'."
}

# Function to generate Vue.js admin panel
generate_vue_crud() {
    local module="$1"
    local class="$2"

    log "Generating Vue.js Admin Panel for $class in module $module..."

    local plural_class
    plural_class=$(pluralize "$(lowercase "$class")")

    local admin_path="resources/js/admin/modules/${module}/${plural_class}"
    local frontend_path="resources/js/frontend/modules/${module}/${plural_class}"

    mkdir -p "$admin_path" || error_exit "Failed to create Admin directory for $class in module '$module'."
    mkdir -p "$frontend_path" || error_exit "Failed to create Frontend directory for $class in module '$module'."

    # Create basic admin and frontend views with CRUD
    echo "<template><div>${class} Admin List</div></template>" > "$admin_path/index.vue"
    echo "<template><div>${class} Admin Create</div></template>" > "$admin_path/create.vue"
    echo "<template><div>${class} Admin Edit</div></template>" > "$admin_path/edit.vue"
    echo "<template><div>${class} Admin Show</div></template>" > "$admin_path/show.vue"

    echo "<template><div>${class} Frontend List</div></template>" > "$frontend_path/index.vue"
    echo "<template><div>${class} Frontend Show</div></template>" > "$frontend_path/show.vue"

    log "Generated Vue.js Admin and Frontend CRUD for $class in module '$module'."
}

main() {
    if [ "$#" -ne 1 ]; then
        usage
    fi

    CONFIG_FILE=$1
    LARAVEL_ROOT=$(pwd)

    if [ ! -f "$CONFIG_FILE" ]; then
        error_exit "Configuration file '$CONFIG_FILE' not found."
    fi

    check_dependencies

    # Resolve Laravel Artisan command
    ARTISAN_CMD=$(resolve_artisan_command "$LARAVEL_ROOT")

    touch "$LOG_FILE" || error_exit "Cannot create log file '$LOG_FILE'. Check permissions."

    log "Starting module generation..."

    modules=$(jq -r '.modules | keys[]' "$CONFIG_FILE") || error_exit "Failed to parse modules from '$CONFIG_FILE'. Ensure it's valid JSON."

    for module in $modules; do
        log "----------------------------------------"
        log "Generating module: $module"
        log "----------------------------------------"

        generate_module "$module" "$ARTISAN_CMD"

        classes=$(jq -r ".modules.\"$module\".classes | keys[]" "$CONFIG_FILE") || error_exit "Failed to parse classes for module '$module'."

        for class in $classes; do
            log "  Generating class: $class"

            CLASS_NAME=$(capitalize "$class")
            CLASS_NAME_LOWER=$(lowercase "$class")

            FEATURES=$(jq -r ".modules.\"$module\".classes.\"$class\".features | join(\",\")" "$CONFIG_FILE") || error_exit "Failed to parse features for class '$class' in module '$module'."
            FIELDS=$(jq -r ".modules.\"$module\".classes.\"$class\".fields | join(\",\")" "$CONFIG_FILE") || error_exit "Failed to parse fields for class '$class' in module '$module'."

            (
                cd "$LARAVEL_ROOT" || error_exit "Failed to navigate to Laravel root '$LARAVEL_ROOT'."

                generate_model "$module" "$class" "$FIELDS" "$ARTISAN_CMD"
                generate_controller "$module" "$class" "$ARTISAN_CMD"
                generate_migration "$module" "$class" "$FIELDS" "$ARTISAN_CMD"
                generate_factory "$module" "$class" "$FIELDS" "$ARTISAN_CMD"
                generate_resource "$module" "$class" "$ARTISAN_CMD"
                generate_form_request "$module" "$class" "$FIELDS" "$ARTISAN_CMD"
                generate_test "$module" "$class" "$FEATURES" "$ARTISAN_CMD"
                generate_routes "$module" "$class" "$ARTISAN_CMD"
                generate_vue_crud "$module" "$class"
            )
        done

        log "Module '$module' generated successfully."
    done

    log "All modules generated successfully."
}

main "$@"