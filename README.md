# inventory
Home inventory management system

## Database

`php setup_database.php` creates the tables in the database from `src/config.php`, or upgrades an existing one.
It applies the migrations from `migrations/` that the database doesn't have yet (they are recorded in `im_migration`), so it's safe to run after every deploy.
A schema change is a new file in `migrations/`, named so it sorts after the existing ones.
