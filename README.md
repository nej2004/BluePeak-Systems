# Sri Ram Fireworks Billing System

A PHP-based billing and inventory management system for Sri Ram Fire Works. The app manages retail and event billing, suppliers, products, employees, system settings, and dashboard reporting with a modern Bootstrap UI and theme support.

## Overview

This project is built for day-to-day business operations in a fireworks retail environment. It provides a practical workflow for billing, stock tracking, supplier management, and reporting, with a dashboard that highlights sales, inventory health, and collections risk.

## Features

- Dashboard with sales totals, stock status, and business insights
- Retail billing and event billing workflows
- Supplier management with search and balance tracking
- Product and stock inventory management
- Employee records management
- System settings for company and billing configuration
- Dark and light theme toggle
- PDF reports for dashboard, sales, and business summaries
- Demo mode fallback when the database is unavailable

## Tech Stack

- PHP 7.4+
- PDO with MySQL / MariaDB
- Bootstrap 5
- Bootstrap Icons
- JavaScript for report generation and UI behavior
- SQLTools support for database access in VS Code

## Project Structure

```text
index.php
SETUP.md
.vscode/
templates/
  dashboard.php
  retail.php
  event.php
  suppliers.php
  products.php
  employees.php
  settings.php
```

## Requirements

- Windows 10+ recommended
- XAMPP or another PHP + MySQL/MariaDB stack
- PHP 7.4 or newer
- MySQL / MariaDB running locally on port 3306

## Quick Start

1. Clone the repository.
2. Open the project in VS Code or copy it to your web root.
3. Start Apache and MySQL from XAMPP.
4. Make sure the database connection details in `index.php` match your local setup.
5. Open the app in your browser.

If you prefer the built-in PHP server, you can use:

```bash
C:\xampp\php\php.exe -S localhost:8000
```

Then open:

```text
http://localhost:8000
```

## Default Database Settings

- Host: `127.0.0.1`
- Port: `3306`
- User: `root`
- Password: blank
- Database: `sri_ram_fireworks`

## Database Notes

The app creates the database and required tables on first successful connection if they do not already exist.

## Documentation

- `SETUP.md` contains the full installation and troubleshooting guide.
- `.vscode/settings.json` contains the SQLTools connection configuration for local development.



## License

Proprietary project for internal business use.
