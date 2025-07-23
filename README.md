# Leadership Summit Laravel Application

This is the Laravel version of the Leadership Summit website, migrated from WordPress.

## Requirements

- PHP 8.1 or higher
- Composer
- Node.js and NPM
- MySQL 5.7 or higher

## Installation

1. Clone the repository:

```
git clone https://github.com/your-organization/leadership-summit-laravel.git
cd leadership-summit-laravel
```

2. Install PHP dependencies:

```
composer install
```

3. Install JavaScript dependencies:

```
npm install
```

4. Create a copy of the .env file:

```
cp .env.example .env
```

5. Generate an application key:

```
php artisan key:generate
```

6. Configure your database in the .env file:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=leadership_summit
DB_USERNAME=leadership_summit
DB_PASSWORD=leadership_summit_password
```

For local development without Docker, you may need to change DB_HOST to 127.0.0.1.

7. Run the database migrations:

```
php artisan migrate
```

8. Seed the database with initial data:

```
php artisan db:seed
```

9. Build the frontend assets:

```
npm run build
```

10. Start the development server:

For Docker:

```
docker-compose up -d
```

For local development:

```
php artisan serve
```

The application will be available at http://localhost:8000.

## Features

- Event management
- Speaker profiles
- Session scheduling
- Registration and ticketing
- User authentication and profiles
- Admin dashboard
- Payment processing

## Directory Structure

- `app/` - Application core code
- `config/` - Configuration files
- `database/` - Migrations and seeders
- `public/` - Publicly accessible files
- `resources/` - Views, language files, assets
- `routes/` - Route definitions
- `storage/` - Application storage
- `tests/` - Automated tests

## Development

### Running Tests

```
php artisan test
```

### Building Assets

```
npm run dev
```

For production:

```
npm run build
```

## Version Control

This project uses Git for version control. The main branches are:

- `main`: Production-ready code
- `develop`: Development branch for ongoing work
- `feature/*`: Feature branches for new functionality
- `bugfix/*`: Branches for bug fixes
- `release/*`: Release preparation branches

### Git Workflow

1. Create a new branch for your feature or bugfix:

```
git checkout -b feature/your-feature-name
```

2. Make your changes and commit them:

```
git add .
git commit -m "Description of your changes"
```

3. Push your branch to the remote repository:

```
git push origin feature/your-feature-name
```

4. Create a pull request to merge your changes into the develop branch.

## License

This project is licensed under the MIT License.
