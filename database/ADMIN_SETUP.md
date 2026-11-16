# Create the Initial Admin Account

Public registration intentionally does not offer the Admin role. Create the
first Admin locally after importing `schema.sql` into your own database:

1. Generate a password hash in a local PHP terminal. Enter a password that is
   not used anywhere else; the value is read at the prompt and is not part of
   the command or repository:

   ```text
   php -r '$password = readline("New local admin password: "); echo password_hash($password, PASSWORD_DEFAULT), PHP_EOL;'
   ```

2. In phpMyAdmin or another local database client, insert the Admin using the
   generated hash. Replace every placeholder locally and do not save or commit
   the completed statement:

   ```sql
   INSERT INTO users (name, email, password_hash, phone, role, status)
   VALUES ('Local Admin', 'admin@your-local-domain.test', '<generated-password-hash>', 'your-local-phone', 'admin', 'active');
   ```

3. Sign in at `/login.php` with the locally selected email and password.

The repository contains no initial Admin account, plaintext password, or
pre-generated login credential. Use a private, disposable database for testing.
