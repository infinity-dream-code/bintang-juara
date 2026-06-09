SIKEU NEW

Laravel 10.

pastikan sudah menginstall
1. PHP 8.1^ (dan extensinya)
2. HTTP Web Server (Nginx/Apache)
3. PHP Composer
4. Database Server (Mysql/Postgre/etc)

Cara install

1. clone repositori ini
2. copy file ```.env.example``` menjadi ```.env```
3. Buka file ```.env```
4. Ubah konfigurasi 'APP_NAME' sesuai dengan nama website
   ```
    APP_NAME="NAMA WEBSITE ANDA"
    ```
6. Tutup file ```.env```
7. Buka terminal pada folder project ini
8. Update laravel
    ``` 
    composer update 
    ```
   jika tidak bisa melakukan update
     ``` 
    composer install 
    ```
9. Buat tabel berdasarkan konfigurasi yang ada pada folder ```/database/migrations```
    ``` 
    php artisan migrate:fresh
    ```
   > ⚠️ **Warning:** Jangan jalankan perintah ini ketika program sudah digunakan instansi!

10. tambahkan data pada tabel berdasarkan konfigurasi yang ada pada folder ```/database/seeders```
    ``` 
    php artisan db:seed 
    ```
    > ⚠️ **Warning:** Jangan jalankan perintah ini ketika program sudah digunakan instansi!

11. Buat _application key_
    ``` 
    php artisan key:generate
    ```
12. untuk menjalankan aplikasi, jalankan perintah
    ``` 
    php artiasn serve 
    ``` 

* jika program sudah digunakan, setelah melakukan modifikasi pada file ```.env``` atau file pada folder ``` /config ```,
  jalankan perintah ``` php artisan optimize:clear``` pada terminal.


* untuk melakukan backup database, silahkan install
    ```
    composer install laravel/spatie-backup
    ```
  lalu jalankan perintah
    ```
    php artisan backup:run --only-db --disable-notifications
    ```
  backup disimpan pada folder storage/app/{APP_NAME}
