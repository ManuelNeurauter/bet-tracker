# BetLedger - Sports Betting Tracker

A comprehensive, production-ready full-stack web application for tracking and analyzing sports betting activity. Built with vanilla PHP, MySQL, and Chart.js, featuring a polished dark-themed UI with deep navy/charcoal backgrounds and green/red accent colors.

## Features

### Core Features
- **User Authentication** - Secure registration and login with bcrypt password hashing
- **Bet Management** - Add, edit, and track bets with comprehensive filtering and sorting
- **Dashboard** - KPI cards, bankroll charts, monthly P&L, and recent bets overview
- **Statistics & Analytics** - Deep-dive statistics with breakdowns by sport, bookmaker, odds range, and more
- **Bookmaker Management** - Track multiple bookmakers with balance and performance metrics
- **Tags & Tipsters** - Organize bets with color-coded tags and track tipster performance
- **Responsive Design** - Mobile-first approach works perfectly on phones, tablets, and desktops

### Bet Types Supported
- Single
- Double
- Treble
- Accumulator (Parlay)
- Lucky 15/31/63
- System Bets
- Each-Way Bets

### Analytics
- Win Rate % and ROI calculations
- Yield % metric
- Profit/Loss tracking by sport, bookmaker, competition, day of week, odds range, etc.
- Bankroll growth visualization
- Cumulative P&L charts
- Average odds and stakes analysis

## Technology Stack

- **Backend**: PHP 8.x (vanilla, no framework dependencies)
- **Database**: MySQL 8.0 / MariaDB
- **Frontend**: Vanilla JavaScript, HTML5, CSS3
- **Charts**: Chart.js for data visualization
- **Database Interface**: PDO (prepared statements for security)
- **Authentication**: PHP sessions with CSRF token protection

## Installation & Setup

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 / MariaDB 10.5+
- Web server (Apache with mod_rewrite, Nginx, or built-in PHP server)
- Composer (optional, for dependency management)

### Option 1: Using XAMPP (Windows/Mac/Linux)

1. **Download and Install XAMPP**
   - Download from https://www.apachefriends.org
   - Install to your preferred location

2. **Extract BetLedger**
   ```bash
   cd C:\xampp\htdocs  # Windows
   # or
   cd /Applications/XAMPP/htdocs  # Mac
   # or
   cd /opt/lampp/htdocs  # Linux
   
   git clone <repository> bet-tracker
   # or extract the ZIP file
   ```

3. **Start XAMPP Services**
   - Start Apache and MySQL services from the XAMPP Control Panel

4. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a new database: `bet_tracker_db`
   - Go to "Import" tab, select the `database/schema.sql` file from the project
   - Click "Go" to execute the SQL

5. **Configure Database Credentials** (if not using defaults)
   - Edit `config/config.php`
   - Update `DB_HOST`, `DB_USER`, `DB_PASS` with your credentials

6. **Access the Application**
   - Navigate to: http://localhost/bet-tracker/public
   - Register a new account
   - Start tracking bets!

### Option 2: Using Docker

1. **With Docker Compose**
   ```bash
   cd bet-tracker
   docker-compose up -d
   ```

2. **Import Database Schema**
   ```bash
   docker exec bet-tracker-db mysql -u root -ppassword bet_tracker_db < database/schema.sql
   ```

3. **Access the Application**
   - http://localhost:8000

### Option 3: Using PHP Built-in Server (Development)

1. **Navigate to Project**
   ```bash
   cd bet-tracker
   ```

2. **Create Database**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. **Update Config** (if needed)
   - Edit `config/config.php` with your database credentials

4. **Start Server**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Access Application**
   - http://localhost:8000

### Option 4: Using Nginx

1. **Install PHP-FPM**
   ```bash
   sudo apt-get install php-fpm php-mysql
   ```

2. **Configure Nginx**
   ```nginx
   server {
       listen 80;
       server_name localhost;
       root /path/to/bet-tracker/public;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
           fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
       }
   }
   ```

3. **Start Nginx**
   ```bash
   sudo systemctl start nginx
   ```

## Database Schema

The application uses a comprehensive schema with the following main tables:

```
users                   - User accounts and preferences
bookmakers             - Betting platforms and balances
sports                 - Sports categories
competitions           - Sports competitions
bets                   - Main bet records
bet_legs               - Support for accumulator bets
tags                   - Customizable bet labels
bet_tags               - Association between bets and tags
tipsters               - Tipster/expert tracking
bet_tipsters           - Association between bets and tipsters
bankroll_snapshots     - Daily bankroll history
bankroll_adjustments   - Deposits, withdrawals, bonuses
```

See `database/schema.sql` for full schema definition.

## File Structure

```
bet-tracker/
├── public/
│   ├── index.php              # Main router/entry point
│   ├── css/
│   │   └── style.css          # Dark theme stylesheet
│   └── js/
│       └── main.js            # Frontend functionality
├── src/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── BetController.php
│   │   ├── StatisticsController.php
│   │   ├── BookmakerController.php
│   │   ├── TagController.php
│   │   ├── TipsterController.php
│   │   └── SettingsController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Bet.php
│   │   ├── Bookmaker.php
│   │   ├── Tag.php
│   │   ├── Tipster.php
│   │   └── Sport.php
│   └── views/
│       ├── layout.php
│       ├── auth/
│       ├── bets/
│       ├── bookmakers/
│       ├── tags/
│       ├── tipsters/
│       ├── dashboard.php
│       ├── statistics.php
│       ├── settings.php
│       └── 404.php
├── config/
│   ├── config.php             # Configuration constants
│   ├── Database.php           # Database connection class
│   └── helpers.php            # Utility functions
├── database/
│   └── schema.sql             # Database schema and seed data
├── .htaccess                  # Apache URL rewriting
└── README.md
```

## Configuration

Edit `config/config.php` to customize:

- Database credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`)
- Application settings (timezone, environment)
- Currency options
- Color theme
- Pagination settings

## Usage

### Getting Started

1. **Register an Account**
   - Go to /register
   - Enter username, email, password
   - Select your preferred currency and timezone
   - Enter starting bankroll (optional)

2. **Add a Bookmaker**
   - Navigate to Bookmakers
   - Click "Add Bookmaker"
   - Enter bookmaker details and current balance

3. **Record Your First Bet**
   - Click "Add Bet" or go to /bets/add
   - Fill in bet details (event, odds, stake, selection)
   - Select sport and bookmaker
   - Submit

4. **Track & Analyze**
   - View Dashboard for overview
   - Check Statistics for detailed analysis
   - Filter bet history by sport, bookmaker, status, etc.

### Key Pages

- **Dashboard** (`/`) - Overview with KPIs and charts
- **Bets** (`/bets`) - View, filter, and manage all bets
- **Add Bet** (`/bets/add`) - Record new bets
- **Statistics** (`/statistics`) - Deep analytics
- **Bookmakers** (`/bookmakers`) - Manage betting platforms
- **Tags** (`/tags`) - Organize bets with labels
- **Tipsters** (`/tipsters`) - Track expert predictions
- **Settings** (`/settings`) - User preferences

## Security Features

- **Password Hashing**: bcrypt with cost factor 12
- **CSRF Protection**: Token validation on all forms
- **Prepared Statements**: All database queries use PDO prepared statements
- **Input Sanitization**: XSS protection via htmlspecialchars()
- **Session Management**: Secure PHP session handling with configurable lifetime
- **SQL Injection Prevention**: Parameter binding on all queries

## API Endpoints

The application uses a simple routing system. All routes go through `public/index.php`:

### Authentication
- `GET /login` - Show login form
- `POST /login` - Handle login
- `GET /register` - Show registration form
- `POST /register` - Handle registration
- `GET /logout` - Logout user

### Bets
- `GET /bets` - List all bets
- `GET /bets/add` - Show add bet form
- `POST /bets/add` - Create bet
- `GET /bets/{id}` - View bet details
- `GET /bets/{id}/edit` - Show edit form
- `POST /bets/{id}/edit` - Update bet
- `POST /bets/{id}/delete` - Delete bet

### Management Pages
- `GET /bookmakers` - List bookmakers
- `GET /bookmakers/add` - Add bookmaker form
- `POST /bookmakers/add` - Create bookmaker
- `GET /tags` - List tags
- `GET /tipsters` - List tipsters
- `GET /statistics` - Analytics page
- `GET /settings` - Settings page

## Calculations & Formulas

### Key Metrics

**ROI (Return on Investment) %**
```
ROI = (Total Profit / Total Staked) * 100
```

**Yield %**
```
Yield = (Total Profit / Total Returned) * 100
```

**Win Rate %**
```
Win Rate = (Won Bets / Total Bets) * 100
```

**Potential Return**
```
Potential Return = Stake × Odds
```

**Profit/Loss**
```
P&L = Actual Return - Stake
```

## Customization

### Theme Colors

Edit the CSS variables in `public/css/style.css`:

```css
:root {
    --color-primary-bg: #0f1117;      /* Main background */
    --color-success: #00d084;          /* Wins (green) */
    --color-danger: #ff4757;           /* Losses (red) */
    --color-warning: #ffa502;          /* Pending (orange) */
    --color-info: #3498db;             /* Info (blue) */
}
```

### Adding Custom Fields

1. Add column to appropriate database table in `database/schema.sql`
2. Run migration:
   ```sql
   ALTER TABLE bets ADD COLUMN new_field VARCHAR(255);
   ```
3. Update model class to handle new field
4. Update controller to process new field
5. Update view templates

## Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check credentials in `config/config.php`
- Ensure database `bet_tracker_db` exists
- Check file permissions

### Blank Page / 500 Error
- Check PHP error logs
- Enable error reporting in `config/config.php` (set `APP_ENV` to 'development')
- Verify all model and controller files exist

### Routes Not Working
- Ensure `.htaccess` is in `public/` directory
- Enable mod_rewrite in Apache: `a2enmod rewrite`
- If using Nginx, check server configuration
- Verify PHP has write permissions

### Session Issues
- Check PHP session.save_path is writable
- Verify session_start() is called before any output
- Clear browser cookies if needed

## Performance Optimization

- Database indexes on frequently queried columns (already included in schema)
- CSS and JS are minifiable for production
- Prepared statements prevent SQL injection and improve query planning
- Pagination on bet lists for faster loading
- Consider caching for dashboard KPIs with heavy calculations

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Android)

## License

This project is provided as-is for personal and commercial use.

## Support & Documentation

For issues, questions, or feature requests:
1. Check the troubleshooting section above
2. Review code comments in relevant files
3. Check database schema in `database/schema.sql`
4. Review controller logic for business rules

## Future Enhancements

Potential features for future versions:
- API endpoints for mobile app integration
- Advanced filtering and saved filters
- Bet slip builder with multiple selections
- Email notifications for pending bets
- Automated backup and export
- Multi-user teams/accounts
- Social sharing of stats
- Integration with live odds APIs
- Advanced probability calculations
- Blockchain-based audit trail

---

**Version**: 1.0.0  
**Last Updated**: 2026  
**Author**: BetLedger Development Team
