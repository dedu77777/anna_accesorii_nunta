# Production security settings

Set these environment variables in the PHP hosting environment before serving requests:

- `COOKIE_VALIDATION_KEY`: a new, random, private signing key. Rotate the old key because it was stored in source code. Rotation invalidates existing signed cookies.
- `DB_DSN`: the production MySQL PDO DSN, for example `mysql:host=localhost;dbname=your_database`.
- `DB_USER` and `DB_PASSWORD`: a dedicated production database account with only the permissions this application needs.
- `APP_ENV=prod`. The web entry point uses `APP_ENV` and defaults to production if it is absent. Do not rely on `YII_ENV` to control web debug mode.
- `APP_HTTPS=1` when TLS terminates at a reverse proxy and PHP does not receive `HTTPS=on`. This ensures session and CSRF cookies have the Secure flag.

Do not set `APP_ENV=dev` or enable `YII_DEBUG` in production. Debug and Gii are enabled only when the web entry point derives the development environment from `APP_ENV`.

Configure the web server document root to this project's `web/` directory. Do not expose `config/`, `runtime/`, `vendor/`, migrations, or project root. Remove or deny access to `web/index-test.php` on production hosting, and do not deploy `create-test-admin.php`. The test entry point also requires both `APP_ENV=test` and `YII_ENV=test` plus loopback access; the provisioning script requires CLI, `APP_ENV=dev` or `test`, and local `DEV_ADMIN_EMAIL`/`DEV_ADMIN_PASSWORD` values. Enforce HTTPS at the web server, set `APP_HTTPS=1`, and confirm response cookies contain `HttpOnly`, `SameSite=Lax`, and `Secure` on the live site. Confirm CSP, Permissions-Policy and HSTS headers on HTTPS responses. The current CSP allows inline scripts and styles to preserve the existing site; tightening it requires moving or nonce-protecting those assets.

Apply the admin login failure-table migration before enabling login. Admin login records failed attempts for a normalized email (maximum five in a rolling 60 minutes) and a separate IP abuse scope (maximum 20). The sixth failed-email attempt receives HTTP 429 even if the password is correct. Both scopes use InnoDB row locks; verify both tables use InnoDB on the host. Admin sessions expire after 30 minutes of inactivity or eight hours overall.

Use a separate MySQL test database for concurrency testing. Never point tests at the production or current development database.

## Checkout and email configuration

- `ORDER_OWNER_EMAIL` is the owner recipient. Set it to the intended owner address in the hosting environment.
- `ORDER_FROM_EMAIL` is a sender address authorized by the SMTP account.
- `PUBLIC_SITE_URL` must be the final public HTTPS origin, such as `https://your-domain.example`. Customer email images are included only when their saved path is under the application's `web/images/products/` directory and the file exists.
- `SMTP_DSN` configures Symfony Mailer. For implicit TLS, use `smtps://USERNAME:PASSWORD@SMTP_HOST:465`. For required STARTTLS, use `smtp://USERNAME:PASSWORD@SMTP_HOST:587?require_tls=true`. URL-encode reserved characters in the username and password. Store the DSN outside source control. Configure the SMTP host, port, username, password, encryption, and sender using your mail provider's verified values.

Without `SMTP_DSN`, checkout still saves orders, but the outbox worker records delivery attempts as failed and sends nothing. The confirmation page does not claim email delivery. Do not process production notifications until SMTP and both email addresses are configured.

Owner notifications use each order item's saved primary-image path and embed a compact JPEG thumbnail with a CID, so the image travels with the email. The owner email shows a fallback label if the saved file is unavailable when the outbox worker runs. PHP GD is needed to make inline thumbnails; when GD is unavailable, a valid `PUBLIC_SITE_URL` provides an HTTPS image fallback. Product files must remain available for delayed notifications. Confirm rendering in the intended email clients with a controlled test message before production use.

The successful-order quota uses the submitted email (trimmed and lowercased) and phone (digits only) as separate identities. An order counts against both; reaching three orders on either identity in a rolling 30 minutes blocks another order. Public IP is excluded from this quota, so unrelated customers can share an IP. The separate IP checkout-request limit remains 20 submissions per 60 seconds. Guest contact details are not verified: changing both email and phone can bypass the order quota, while customers sharing an email or phone also share its quota. Existing historical IP quota events are ignored.

After migration, schedule `php yii order-email/process` every minute using a host cron job with the same database and SMTP environment. It claims pending rows in MySQL, attempts at most five sends per notification, and retries failures after a minute. A stale processing claim can be retried after ten minutes. An SMTP acknowledgment does not prove inbox delivery. A network failure after SMTP accepts a message may cause a duplicate on retry; the outbox prevents duplicate rows, but cannot guarantee exactly-once external delivery.

## Local manual checkout test

1. Export `COOKIE_VALIDATION_KEY`, `DB_DSN`, `DB_USER`, and `DB_PASSWORD` for a local test database. Point `DB_DSN` at that database; never use a production or existing customer database for destructive tests.
2. Run `php yii migrate --interactive=0`. Confirm `orders`, `order_items`, `order_email_outbox`, `order_rate_limit_lock`, and `order_rate_limit_event` exist and use InnoDB.
3. Start the site with `php -S 127.0.0.1:8080 -t web web/index.php` using the same environment. Add a product, open `/cos`, and choose “Continuă spre comandă”.
4. Try pickup and home delivery. Home delivery requires an address. Complete the form with an email address you own and submit. The confirmation page should show a pending order and the cart should be empty.
5. Inspect the test database with `SELECT id,order_number,status,total FROM orders ORDER BY id DESC LIMIT 5;`, `SELECT order_id,product_title,unit_price,quantity,line_total,product_image FROM order_items ORDER BY id DESC LIMIT 10;`, and `SELECT order_id,kind,status,attempts FROM order_email_outbox ORDER BY id DESC LIMIT 10;`.
6. Resubmit the same form or refresh the confirmation page: no duplicate order should appear. The successful-order quota allows three orders per normalized email or phone in a rolling 30 minutes; the fourth attempt matching either contact returns HTTP 429 with `Retry-After`. Different customers sharing a public IP have separate order quotas. Checkout submissions also have a separate IP request limit of 20 per 60 seconds.
7. For a safe email preview, use a test SMTP inbox or a local mail-catching server. Set `SMTP_DSN` to that server, `ORDER_FROM_EMAIL` to a test sender, `ORDER_OWNER_EMAIL` to an address you control, and `PUBLIC_SITE_URL` to a publicly reachable HTTPS test site with the product images. Run `php yii order-email/process` and inspect both messages and outbox status. Customer HTML uses the product image URL; owner HTML embeds each available primary product image as a CID thumbnail.
8. For one controlled external delivery test, use an SMTP account you own, submit with your own customer address, set the owner recipient to an address you own, run the worker, and verify both inboxes. Only after confirming this flow, set the owner recipient to the intended address and verify that notification separately.

The site's public document root, HTTPS termination, SMTP deliverability, and header behavior must also be checked on staging and again on production hosting.
