# BetLedger Quick Start Guide

Get BetLedger up and running in 5 minutes!

## Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or MariaDB 10.5+
- Web server (Apache, Nginx, or PHP built-in)

## Option 1: Quick Start (5 minutes)

### Windows

1. **Download and extract** BetLedger to a folder
2. **Edit .env** file with your database credentials:
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=your_password
   DB_NAME=bet_tracker_db
   ```
3. **Open command prompt** and navigate to the folder
4. **Run setup**:
   ```cmd
   setup.bat
   ```
5. **Create database** using phpMyAdmin or command line:
   ```cmd
   mysql -u root -p < database\schema.sql
   ```
6. **Start server**:
   ```cmd
   php -S localhost:8000 -t public
   ```
7. **Open browser**: http://localhost:8000
8. **Register** a new account

### Mac/Linux

1. **Download and extract** BetLedger
2. **Edit .env** file:
   ```bash
   nano .env
   ```
3. **Run setup**:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```
4. **Start server**:
   ```bash
   php -S localhost:8000 -t public
   ```
5. **Open browser**: http://localhost:8000

## Option 2: Docker (Easiest)

1. **Navigate to project** folder
2. **Start services**:
   ```bash
   docker-compose up -d
   ```
3. **Open browser**: http://localhost:8000
4. **Register** account

## First Steps After Setup

### 1. Register Account
- Go to /register
- Create username and password
- Select currency (USD, EUR, GBP, AUD, CAD)
- Set timezone
- Enter starting bankroll (optional)

### 2. Add Bookmaker
- Click "Bookmakers" in sidebar
- Click "+ Add Bookmaker"
- Enter name (e.g., "Bet365")
- Enter current balance
- Save

### 3. Record Your First Bet
- Click "+ Add Bet" button
- Fill in details:
  - Event Name (e.g., "Man Utd vs Liverpool")
  - Sport (Football)
  - Bookmaker (select one)
  - Bet Type (Single)
  - Selection (e.g., "Man Utd Win")
  - Odds (e.g., 1.85)
  - Stake (e.g., £100)
  - Potential Return (auto-calculated)
- Click "Save Bet"

### 4. Settle the Bet
- When bet result is known, click the bet in history
- Click "Edit"
- Change Status to Won/Lost/Void
- If Won, enter Actual Return
- Save

### 5. View Results
- Go to Dashboard to see overview
- Go to Statistics for detailed analysis
- Charts show bankroll growth and P&L by sport

## Key Features Quick Reference

| Feature | URL | What It Does |
|---------|-----|-------------|
| Dashboard | / | Overview with KPIs and charts |
| All Bets | /bets | View and filter all bets |
| Add Bet | /bets/add | Record new bet |
| Statistics | /statistics | Deep analytics |
| Bookmakers | /bookmakers | Manage betting accounts |
| Tags | /tags | Create labels for bets |
| Tipsters | /tipsters | Track expert performance |
| Settings | /settings | User preferences |

## Common Tasks

### Export Bet History to CSV
1. Go to Bets page
2. Click "Export to CSV" button (coming soon)

### Change Currency/Timezone
1. Go to Settings
2. Update Currency and Timezone
3. Click Save

### Calculate ROI
- Automatically shown on Statistics page
- Formula: (Total Profit / Total Staked) × 100

### Filter Bets
1. Go to Bets page
2. Use filters:
   - Status (Pending, Won, Lost, Void)
   - Sport
   - Bookmaker
   - Date range
   - Odds range
   - Stake range
3. Click filter button to apply

## Troubleshooting

### Database Connection Error
1. Verify MySQL is running
2. Check .env credentials match your MySQL
3. Ensure database exists: `bet_tracker_db`

### Page Shows Blank / 404 Error
1. Check URL starts with /public/ or is using rewrite
2. Verify .htaccess in public/ folder
3. Check PHP error logs

### Lost Password
- Currently no password reset
- Contact admin or delete user from database

## Sample Data

To populate with test data:
1. In phpMyAdmin, import `database/seed_data.sql`
2. Or: `mysql -u root -p bet_tracker_db < database/seed_data.sql`
3. Login with test account: test@example.com

## Next Steps

1. **Read Full README** for advanced setup options
2. **Check Documentation** in README for all features
3. **Explore** the application interface
4. **Add Real Data** for your actual bets
5. **Customize** colors and settings for your preferences

## Getting Help

1. **Check README.md** - comprehensive documentation
2. **Review CONTRIBUTING.md** - development guidelines
3. **Check code comments** in PHP files
4. **Review database schema** in database/schema.sql

## Tips for Success

- ✓ Track ALL bets, not just winners
- ✓ Update settled bets promptly for accurate stats
- ✓ Use tags to categorize bets (high confidence, risky, etc.)
- ✓ Review statistics regularly for patterns
- ✓ Monitor ROI and yield metrics
- ✓ Set realistic bankroll limits
- ✓ Track bookmaker balances for reconciliation

## Performance Optimization

For best performance:
- Delete old bets after archiving
- Index frequently filtered columns
- Use modern browser (Chrome 90+)
- Enable caching if using shared hosting

## Security Reminder

- ✓ Use strong passwords (8+ characters, mix of types)
- ✓ Don't share login credentials
- ✓ Keep PHP and database updated
- ✓ Regularly backup database
- ✓ Use HTTPS in production

---

**Happy betting tracking!**

For more info: See README.md
