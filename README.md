# Online Store

Run database setup and additive schema migrations from the project directory with PHP CLI:

```powershell
php install.php
```

The installer is intentionally unavailable over HTTP. It preserves existing users, products, categories, and orders. Signed-in carts are stored in the database; guest carts remain in the session until sign-in.
