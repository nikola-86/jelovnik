# Jelovnik

A full-stack meal selection management application that demonstrates modern web development practices with clean architecture and SOLID principles.

## Overview

Jelovnik is a demo application for managing employee meal selections. It demonstrates how to track meal choices, automatically manage employee data, and send notifications via various channels. The application serves as a practical example of a well-structured full-stack application with clear separation of concerns and extensible design patterns. Built with interface-based architecture, it currently includes concrete implementations for CSV data import (`CsvDataProvider`) and Slack notifications (`SlackNotifier`), while demonstrating how to easily extend support for different data sources (Excel, JSON, APIs, etc.) and notification channels (Microsoft Teams, Email, Discord, etc.) through simple interface implementations.

## Tech Stack

### Backend
- **Laravel 12** (PHP 8.3)
- **MariaDB** (MySQL-compatible database)
- **Docker** (nginx + php-fpm)

### Frontend
- **React 18** with **Vite**
- Integrated directly into Laravel (no separate frontend project)

## Features

- **Flexible Data Import**: Upload CSV files containing meal choices per employee and date (extensible to support Excel, JSON, APIs, and more)
- **Automatic Data Management**: Automatically creates or updates employee records and their meal choices
- **Multi-Channel Notifications**: Sends notifications via Slack with optional employee mentions (easily extensible to Microsoft Teams, Email, Discord, and other channels)
- **Dashboard & Statistics**: View all meal choices and basic statistics including:
  - Total number of meal choices
  - Count of choices with/without notification IDs

## Architecture

The codebase is organized to demonstrate:

- **SOLID Principles**: Clean, maintainable code following SOLID design principles
- **Layer Separation**: Clear separation between controllers, services, models, and jobs
- **Interface-Based Design**: Uses interfaces (`DataProviderInterface`, `NotifierInterface`) for:
  - Easier testing and mocking
  - Future extensibility (support for different data sources and notification channels)
  - Dependency inversion and loose coupling

## Extensibility

The application is built with extensibility in mind, using interface-based design patterns that make it easy to swap implementations without modifying core business logic.

### Data Providers

The `DataProviderInterface` allows the application to accept meal choice data from various sources. Currently implemented:

- **CSV Files** (`CsvDataProvider`) - Upload CSV files with meal choices

**Easy to extend with:**
- Excel files (`.xlsx`, `.xls`)
- JSON files or APIs
- REST API endpoints
- GraphQL APIs
- Google Sheets integration
- Database queries
- Webhook receivers

To add a new data provider, simply implement the `DataProviderInterface` and register it in `AppServiceProvider`. The interface requires a single method that returns an array of structured data rows.

### Notifiers

The `NotifierInterface` enables sending notifications through different channels. Currently implemented:

- **Slack** (`SlackNotifier`) - Sends notifications to Slack channels with optional user mentions

**Easy to extend with:**
- **Microsoft Teams** - Similar webhook-based approach as Slack
- Email notifications (SMTP)
- Discord webhooks
- SMS services (Twilio, etc.)
- Push notifications
- Custom notification services

To add a new notifier (e.g., Microsoft Teams), implement the `NotifierInterface` and update the service binding in `AppServiceProvider`. The interface is simple and requires only a `notify()` method that accepts employee and meal choice data.

## Installation

### Prerequisites

- Docker and Docker Compose
- Node.js and npm
- Composer (PHP package manager)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd jelovnik
   ```

2. **Copy environment configuration**
   ```bash
   cp .env.example .env
   ```

3. **Configure environment variables**
   
   Edit `.env` and set your configuration values. At minimum, ensure the following are configured:
   - `APP_URL` - Your application URL (default: `http://localhost`)
   - Database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
   - `SLACK_WEBHOOK_URL` - Your Slack webhook URL for notifications
   
   See `.env.example` for all available configuration options.

4. **Install PHP dependencies**
   ```bash
   composer install
   ```

5. **Start Docker environment**
   ```bash
   docker compose up -d
   ```

6. **Install frontend dependencies and build**
   ```bash
   npm install
   npm run build
   ```

7. **Run database migrations**
   ```bash
   docker exec jelovnik-php php artisan migrate --force
   ```

8. **Access the application**
   
   Open [http://localhost](http://localhost) in your browser.

## Usage

Once the application is running, you can:

1. Upload a CSV file with meal choices (format: employee, date, meal choice)
2. View all meal selections in the dashboard
3. Review statistics about meal choices and employee Slack ID coverage
4. Receive automatic Slack notifications when new meal choices are processed

## Testing

The application includes comprehensive unit tests demonstrating testability through interface-based design and dependency injection.

### Test Coverage

The test suite includes:

- **CsvDataProvider**: Tests CSV parsing, column mapping, date normalization, and error handling
- **SlackNotifier**: Tests message building, recipient formatting, HTTP requests (mocked), and error handling
- **MealChoiceProcessor**: Tests data processing, employee creation/updates, meal choice management, and job dispatching
- **Models**: Tests relationships, static methods, and data formatting

All tests use Laravel's testing features including:
- Database transactions (in-memory SQLite for speed)
- HTTP faking for external API calls
- Queue faking for job testing
- Model factories for test data generation

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
