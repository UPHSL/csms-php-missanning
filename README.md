# Community Services Management System

The **Community Services Management System (CSMS)** is the semester-long software development project for the Programming Languages Laboratory.

This repository contains the **PHP implementation of CSMS using Laravel**.

Students will incrementally develop this application throughout the semester using professional software development practices including Git, GitHub, feature branches, automated testing, code review, Pull Requests, documentation, and incremental software delivery.

## Technology Stack

- PHP
- Laravel
- Composer
- PHPUnit
- Laravel Pint
- SQLite for local development
- Git
- GitHub

## Important

This repository is a **starter project**.

Some classes, services, repositories, validators, tests, and other components may intentionally contain minimal implementations or placeholders.

Do not attempt to complete future functionality unless it is required by the current Moodle activity or ticket.

Students are expected to implement the system incrementally throughout the semester.

## Prerequisites

Before starting, verify that the following tools are installed.

```bash
php -v
composer --version
git --version
```

You should also have a suitable development environment such as:

- Visual Studio Code
- PhpStorm
- another PHP-compatible IDE or editor

## Initial Setup

After cloning your GitHub Classroom repository, open a terminal inside the project directory.

### 1. Install PHP dependencies

```bash
composer install
```

The `vendor/` directory is intentionally not stored in Git and must be generated locally.

### 2. Create the environment file

macOS or Linux:

```bash
cp .env.example .env
```

Windows Command Prompt:

```cmd
copy .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 3. Generate the application key

```bash
php artisan key:generate
```

### 4. Prepare the SQLite database

Create the following file:

```text
database/database.sqlite
```

macOS or Linux:

```bash
touch database/database.sqlite
```

Windows PowerShell:

```powershell
New-Item database/database.sqlite -ItemType File
```

Windows Command Prompt:

```cmd
type nul > database\database.sqlite
```

### 5. Run database migrations

```bash
php artisan migrate
```

### 6. Run the automated tests

```bash
php artisan test
```

All baseline tests should pass before beginning a development activity.

### 7. Start the application

```bash
php artisan serve
```

Laravel should display the local development address, normally:

```text
http://127.0.0.1:8000
```

Open the address in your browser.

## Health Check

While the application is running, open:

```text
http://127.0.0.1:8000/health
```

The endpoint should return a JSON response indicating that the application is operational.

## Code Formatting

This project uses **Laravel Pint** for PHP code formatting.

Check or format the project using:

```bash
./vendor/bin/pint
```

Windows PowerShell:

```powershell
.\vendor\bin\pint
```

Students should review the resulting changes before committing them.

## Automated Testing

Run the complete test suite using:

```bash
php artisan test
```

When working on a specific test, Laravel also supports filtered execution.

Example:

```bash
php artisan test --filter=ResidentTest
```

Students are expected to run the relevant tests during development and the complete test suite before submitting their work.

## Development Workflow

For each development activity:

1. Update your local `main` branch.
2. Create the required feature branch.
3. Implement only the requirements of the assigned ticket.
4. Add or update automated tests.
5. Run the relevant automated tests.
6. Run the complete test suite.
7. Run Laravel Pint.
8. Review your changes with Git.
9. Commit using the required commit message.
10. Push the feature branch.
11. Create a Pull Request when required by the activity.

Do not develop semester activities directly on `main` unless specifically instructed.

## Documentation

Read the project documentation before beginning development:

```text
docs/project-overview.md
docs/architecture.md
docs/developer-handbook.md
docs/git-cheatsheet.md
docs/release-workflow.md
```

Students should also read the instructions associated with the current Moodle activity or development ticket.

## Developer Identification

Complete:

```text
ABOUT_THE_DEVELOPER.md
```

during developer onboarding.

Do not remove this file during the semester.

## Academic Integrity

All submitted code must represent the student's own work.

Students may use documentation, development tools, and approved learning resources, but they must understand the code they submit and must be able to explain their implementation, tests, Git history, and design decisions.

Do not copy another student's implementation or allow another student to submit your work.

## Project Principle

Develop the system incrementally.

Do not try to build the entire application at once.

Each activity introduces a small part of the system and builds upon work completed in previous activities.