Make sure to free the Laravel related docker port by stopping other local php83 docker instances
./install-custom-api.sh
copy and edit cms_module
sail artisan vcapi:generate
sail artisan migrate
visit localhost:8080
