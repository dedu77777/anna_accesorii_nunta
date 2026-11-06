"""Inspect queued owner emails in Yii file transport on a private MySQL instance."""
import email.policy
from email.parser import BytesParser
from pathlib import Path
import http.cookiejar
import os
import re
import subprocess
import urllib.parse
import urllib.request

root = Path(__file__).resolve().parents[1]
base = os.environ['CHECKOUT_TEST_URL']
socket = os.environ['CHECKOUT_TEST_SOCKET']
assert base.startswith('http://127.0.0.1:')
assert socket.startswith('/private/tmp/anna-checkout-mysql.') and socket.endswith('/mysql.sock')
db = 'anna_checkout_security_test'

def sql(query):
    result = subprocess.run(['/usr/local/mysql/bin/mysql', '--no-defaults', '--protocol=socket',
        '--socket=' + socket, '-u', 'root', '--batch', '--skip-column-names', db, '-e', query],
        text=True, capture_output=True, check=True)
    return result.stdout.strip()

assert sql('SELECT @@datadir, @@skip_networking').startswith(os.path.dirname(socket) + '/\t1')
engines = sql("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('orders','order_items','order_email_outbox','order_rate_limit_event','order_rate_limit_lock')")
assert engines.splitlines() == ['InnoDB'] * 5, engines
sql('DELETE FROM orders')
sql('DELETE FROM cart')
sql('DELETE FROM api_rate_limit')
sql('DELETE FROM order_rate_limit_event')
sql('DELETE FROM order_rate_limit_lock')

class Visitor:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.get('/')

    def get(self, path):
        with self.opener.open(base + path) as response:
            return response.status, response.read().decode()

    def post(self, path, fields):
        csrf = re.search(r'name="_csrf" value="([^"]+)"', self.get('/')[1]).group(1)
        body = urllib.parse.urlencode({'_csrf': csrf, **fields}).encode()
        with self.opener.open(urllib.request.Request(base + path, body, method='POST')) as response:
            return response.status, response.geturl()

    def order(self, email_address, phone):
        for product_id in ('1', '2'):
            assert self.post('/adauga-in-cos', {'product_id': product_id, 'quantity': '1'})[0] == 200
        page = self.get('/plaseaza-comanda')[1]
        key = re.search(r'name="idempotency_key" value="([a-f0-9]{64})"', page).group(1)
        status, location = self.post('/plaseaza-comanda', {
            'full_name': 'Owner Email Test', 'email': email_address, 'phone_number': phone,
            'delivery_method': 'pickup', 'privacy_accepted': '1', 'idempotency_key': key,
        })
        assert status == 200 and '/comanda/' in location, (status, location)
        return sql('SELECT MAX(id) FROM orders')

mail_dir = root / 'runtime' / 'mail'
before = set(mail_dir.iterdir()) if mail_dir.exists() else set()
worker_env = dict(os.environ, APP_ENV='test', SMTP_DSN='',
    ORDER_FROM_EMAIL='sender@example.invalid', ORDER_OWNER_EMAIL='anacatan98@gmail.com',
    DB_DSN='mysql:unix_socket=' + socket + ';dbname=' + db,
    DB_USER='root', DB_PASSWORD='', COOKIE_VALIDATION_KEY='isolated-checkout-test-key-2026')

def process_mail():
    result = subprocess.run(['php', 'yii', 'order-email/process'], cwd=root,
        env=worker_env, text=True, capture_output=True, check=True)
    assert 'Processed 2 notification(s).' in result.stdout, result.stdout

def owner_message(previous):
    new_files = set(mail_dir.iterdir()) - previous
    assert len(new_files) == 2, new_files
    messages = [BytesParser(policy=email.policy.default).parsebytes(path.read_bytes()) for path in new_files]
    owner = next(message for message in messages if 'anacatan98@gmail.com' in message['To'])
    html = owner.get_body(preferencelist=('html',)).get_content()
    images = [part for part in owner.walk() if part.get_content_type() == 'image/jpeg']
    return html, images, new_files

first = Visitor().order('owner-image-1@example.invalid', '+37360000111')
snapshots = sql('SELECT product_image FROM order_items WHERE order_id=' + first + ' ORDER BY id').splitlines()
assert len(snapshots) == 2 and all(path.startswith('products/') for path in snapshots), snapshots
for product_id, snapshot in zip((1, 2), snapshots):
    primary = sql('SELECT image FROM product_images WHERE product_id=' + str(product_id) +
        ' ORDER BY sort_order,id LIMIT 1')
    assert snapshot == primary, (snapshot, primary)
replacement = ('products/buchete/buchete-1-3.png' if snapshots[0].endswith('buchete-1-2.png')
    else 'products/buchete/buchete-1-2.png')
primary_id = sql('SELECT id FROM product_images WHERE product_id=1 ORDER BY sort_order,id LIMIT 1')
assert primary_id.isdigit()
sql("UPDATE product_images SET image='" + replacement + "' WHERE id=" + primary_id)
assert sql('SELECT image FROM product_images WHERE product_id=1 ORDER BY sort_order,id LIMIT 1') == replacement
assert sql('SELECT product_image FROM order_items WHERE order_id=' + first + ' ORDER BY id').splitlines() == snapshots
process_mail()
html, images, created = owner_message(before)
assert html.count('<img ') == 2 and len(images) == 2
assert all(image.get_payload(decode=True).startswith(b'\xff\xd8') for image in images)
assert all(len(image.get_payload(decode=True)) < 100000 for image in images)
assert all('cid:' + image['Content-ID'].strip('<>') in html for image in images)
assert 'Cantitate: 1' in html and 'Preț unitar:' in html and 'Total produs:' in html
assert sql("SELECT COUNT(*) FROM order_email_outbox WHERE order_id=" + first + " AND status='sent'") == '2'
print('delayed owner email: 2 snapshotted product images, 2 inline JPEGs, 2 sent outbox rows')

second = Visitor().order('owner-image-2@example.invalid', '+37360000112')
sql("UPDATE order_items SET product_image='products/missing/unavailable.png', product_title='<script>Unsafe</script>' WHERE order_id=" + second + " AND product_id=2")
process_mail()
html, images, _ = owner_message(before | created)
assert html.count('<img ') == 1 and len(images) == 1
assert 'Imagine indisponibilă' in html
assert '&lt;script&gt;Unsafe&lt;/script&gt;' in html and '<script>Unsafe</script>' not in html
assert sql("SELECT COUNT(*) FROM order_email_outbox WHERE order_id=" + second + " AND status='sent'") == '2'
print('missing image: one inline JPEG, fallback text, escaped title, 2 sent outbox rows')
