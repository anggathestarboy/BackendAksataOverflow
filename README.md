# AksataOverflow

AksataOverflow is a web application built with **Laravel** that serves as a platform for sharing knowledge and engaging in discussions. The application allows users to create, view, and interact with various questions and content available on the platform.

This project is developed using the Laravel framework with the goal of providing a structured, maintainable, and scalable web application.

## Installation

### 1. Clone the Repository

Clone the repository to your local environment:

```bash
git clone https://github.com/anggathestarboy/AksataOverflow
cd AksataOverflow
```

### 2. Install Dependencies

Install the required PHP dependencies using Composer:

```bash
composer install
```

### 3. Configure the Environment

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

On Windows:

```bash
copy .env.example .env
```

Configure the database and other environment settings in the `.env` file.

### 4. Generate the Application Key

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 5. Run Database Migrations

Run the database migrations:

```bash
php artisan migrate
```

If the project includes seeders:

```bash
php artisan migrate --seed
```

### 6. Run the Application

Start the Laravel development server:

```bash
php artisan serve
```

The application can then be accessed at:

```text
http://127.0.0.1:8000
```
