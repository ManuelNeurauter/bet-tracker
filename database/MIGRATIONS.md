# Database Migrations

`database/schema.sql` creates a brand-new database. When the schema changes later
(for example when the bookmaker tax columns were added), an existing database does
not pick that change up by itself. Migrations are how those changes reach a
database that already has your bets in it.

Each change lives in its own numbered file in `database/migrations/`. The runner
`database/migrate.php` remembers which files it has applied in the
`schema_migrations` table and only runs the new ones.

## Updating your database

Back up first, then run the migrations from the project root:

```bash
mysqldump -u root -p bet_tracker_db > backup.sql
php database/migrate.php
```

That is all. Run it after every update of the code (for example after `git pull`).
It is safe to run any number of times; when nothing is pending it prints
`Database is up to date.`

To see what has been applied and what is still pending:

```bash
php database/migrate.php status
```

The runner uses the same connection settings as the app (`DB_HOST`, `DB_PORT`,
`DB_USER`, `DB_PASS`, `DB_NAME` from the environment, or the defaults in
`config/config.php`). Override them for one run like this:

```bash
DB_USER=root DB_PASS=secret php database/migrate.php
```

### With Docker

```bash
docker compose exec web php database/migrate.php
```

The `db` container only imports `schema.sql` the first time its volume is created,
so an existing Docker install needs this command after pulling new code.

### With XAMPP on Windows

```bat
C:\xampp\php\php.exe database\migrate.php
```

## Fresh installs

`schema.sql` already contains every migrated change and records those migration
versions in `schema_migrations`, so a new install needs no migrations. Running
`php database/migrate.php` right after importing `schema.sql` just prints
`Database is up to date.`

Databases created before the migration system existed have no `schema_migrations`
table. The runner creates it on its first run and then applies every migration.
The early migrations check what already exists, so they are safe on databases
that already have some of the changes.

## Adding a schema change

1. Create the migration file:

   ```bash
   php database/migrate.php make add_bet_currency
   ```

   This creates `database/migrations/0002_add_bet_currency.sql` (the next free number).

2. Write the SQL in it, one or more statements separated by `;`:

   ```sql
   ALTER TABLE bets ADD COLUMN currency VARCHAR(3) DEFAULT NULL AFTER stake;
   ```

3. Make the same change in `database/schema.sql`, and add the new version to the
   `INSERT INTO schema_migrations` list near the bottom of that file so fresh
   installs do not run it again.

4. Test both paths: import `schema.sql` into an empty database, and run
   `php database/migrate.php` against a copy of an older database.

### PHP migrations

When a change needs logic (only add a column if it is missing, or transform data
row by row), name the file `.php` instead and return a function that receives the
PDO connection:

```php
<?php
return function (PDO $db) {
    if (!columnExists($db, 'bets', 'currency')) {
        $db->exec('ALTER TABLE bets ADD COLUMN currency VARCHAR(3) DEFAULT NULL');
    }
};
```

The helpers `tableExists($db, $table)`, `columnExists($db, $table, $column)` and
`indexExists($db, $table, $index)` are available inside PHP migrations.

## Rules of thumb

- **Never edit a migration that has already been applied** somewhere. Add a new one instead.
- **Keep each migration small.** MySQL commits every `ALTER TABLE` / `CREATE TABLE`
  immediately, so if a migration with several statements fails halfway, the first
  statements stay applied. Small migrations (or PHP migrations that check before
  changing) make a failed run easy to fix and re-run.
- **Migrations only go forward.** To undo a change, write a new migration that reverses it,
  or restore the backup you made before running them.
- If a migration fails, the runner stops, prints the error and does not run the ones
  after it. Nothing is recorded for the failed migration, so fix the problem and run
  the command again.
