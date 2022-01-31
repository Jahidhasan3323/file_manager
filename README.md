# Coding test for Full-Stack Web Developer 

## Installation

Clone the repository

    git clone https://github.com/Jahidhasan3323/file_manager

Switch to the repo folder

    cd file_manager

Install all the dependencies using composer

    composer install

Copy the example env file and make the required configuration changes in the .env file for mail and other stubs

    cp .env.example .env

set `ALLOWED_FILE_EXTENSION` for allowed file type ```.env```

set `MAX_FILE_size` for allowed file max size ```.env```

Generate a new application key

    php artisan key:generate



Run storage link command

 ```php artisan storage:link```

Start the local development server

    php artisan serve

You can now access the server at http://localhost:8000 



