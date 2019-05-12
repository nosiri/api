# NoSiri API

<p align="center">
  <a href="https://github.com/noSiri/api">
    <img src="https://raw.githubusercontent.com/nosiri/pwa/master/public/img/icons/android-chrome-512x512.png" alt="NoSiri logo" width="400" height="400">
  </a>
</p>

## Install Dependencies
```bash
composer install
```
## Configuration
```bash
sudo mv .env.example .env
sudo nano .env
```
## Init Database
```bash
php artisan migrate
```
## Run API
```bash
php -S localhost:8000 -t public
```