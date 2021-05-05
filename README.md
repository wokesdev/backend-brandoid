## Back-End API for BrandoID's Project.

### Please follow steps below to use this back-end on your server.

This is an API for counting income statement along with some features for your business.

If you just need a simple income statement counter, just use the front-end that we already created here, https://github.com/wokesdev/frontend-brandoid.

Install all the packages needed using composer, if you haven't install composer, you can get it on https://getcomposer.org.

    composer install
    
Copy the `.env.example` file and paste it on the same folder, then rename it to `.env`.

Edit the `.env` file and change the value of `APP_NAME`, `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`, then you can change the rest of the variables' value according to your needs.

After that, generate a new key for the application.

    php artisan key:generate
    
Last, you need to migrate all the columns to your database.

    php artisan migrate
    
After you finish all of that stuff, you already can use the API for your needs.
