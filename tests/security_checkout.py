"""HTTP checkout checks against a fresh, private MySQL test instance only."""
import concurrent.futures
import http.cookiejar
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request

base = os.environ['CHECKOUT_TEST_URL']
socket = os.environ['CHECKOUT_TEST_SOCKET']
assert base.startswith('http://127.0.0.1:')
assert socket.startswith('/private/tmp/anna-checkout-mysql.') and socket.endswith('/mysql.sock')
db = 'anna_checkout_security_test'
def sql(query):
    result = subprocess.run(['/usr/local/mysql/bin/mysql', '--no-defaults', '--protocol=socket',
        '--socket=' + socket, '-u', 'root', '--batch', '--skip-column-names', db, '-e', query],
        text=True, capture_output=True)
    assert result.returncode == 0, result.stderr
    return result.stdout.strip()
assert sql('SELECT @@datadir, @@skip_networking').startswith(os.path.dirname(socket) + '/\t1')
isolation = sql('SELECT @@transaction_isolation')
assert isolation in ('REPEATABLE-READ', 'READ-COMMITTED'), isolation
engines = sql("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('orders', 'order_items', 'order_email_outbox', 'order_rate_limit_lock', 'order_rate_limit_event', 'api_rate_limit') ORDER BY TABLE_NAME")
assert len(engines.splitlines()) == 6 and all(row.endswith('\tInnoDB') for row in engines.splitlines()), engines
print('database isolation', isolation, 'engines', engines.replace('\n', ', '))
if os.environ.get('CHECKOUT_TEST_RESET') == '1':
    sql('DELETE FROM orders')
    sql('DELETE FROM cart')
    sql('DELETE FROM api_rate_limit')
    sql('DELETE FROM order_rate_limit_event')
    sql('DELETE FROM order_rate_limit_lock')
assert sql('SELECT COUNT(*) FROM orders') == '0'

class Visitor:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar),
            urllib.request.HTTPRedirectHandler())
        self.get('/')
    def get(self, path):
        with self.opener.open(base + path) as r:
            return r.status, r.geturl(), r.read().decode()
    def post(self, path, fields, csrf=True, follow=True, headers=None):
        if csrf: fields = {'_csrf': self.csrf, **fields}
        request = urllib.request.Request(base + path, urllib.parse.urlencode(fields).encode(), method='POST')
        for name, value in (headers or {}).items(): request.add_header(name, value)
        try:
            with self.opener.open(request) as r: return r.status, r.geturl(), r.read().decode(), dict(r.headers)
        except urllib.error.HTTPError as e: return e.code, e.geturl(), e.read().decode(), dict(e.headers)
    @property
    def csrf(self):
        return re.search(r'name="_csrf" value="([^"]+)"', self.get('/')[2]).group(1)

def checkout(v):
    status, url, page = v.get('/plaseaza-comanda')
    assert status == 200, (status, url, page[:100])
    key = re.search(r'name="idempotency_key" value="([a-f0-9]{64})"', page).group(1)
    csrf = re.search(r'name="_csrf" value="([^"]+)"', page).group(1)
    return key, csrf

def data(key, email='test-owned@example.invalid', phone='+37360000001', method='pickup', **extra):
    return {'full_name': 'Test Customer', 'email': email, 'phone_number': phone,
        'delivery_method': method, 'privacy_accepted': '1', 'idempotency_key': key, **extra}

a = Visitor()
print('empty checkout', a.get('/plaseaza-comanda')[:2])
assert a.get('/plaseaza-comanda')[1].endswith('/cos')
add = a.post('/adauga-in-cos', {'product_id': '1', 'quantity': '2'})
assert add[0] == 200, add[:3]
key, csrf = checkout(a)
bad = a.post('/plaseaza-comanda', data(key, email='bad', full_name='<script>alert(1)</script>'))
print('invalid checkout', bad[0], 'escaped=', '&lt;script&gt;' in bad[2])
assert bad[0] == 200 and '&lt;script&gt;' in bad[2] and sql('SELECT COUNT(*) FROM orders') == '0'
wrong = a.post('/plaseaza-comanda', data(key, method='home_delivery'))
assert wrong[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '0'
bad_phone = a.post('/plaseaza-comanda', data(key, phone='-------'))
assert bad_phone[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '0'
injection = a.post('/plaseaza-comanda', data(key, email="x' OR 1=1 --"))
assert injection[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '0'
tampered = a.post('/plaseaza-comanda', data(key, total='0.01'))
assert tampered[0] == 422
oversized = a.post('/plaseaza-comanda', data(key, additional_information='x' * 17000))
assert oversized[0] == 413
array_input = a.post('/plaseaza-comanda', {'idempotency_key': key,
    'full_name[]': 'x', 'email': 'test-owned@example.invalid', 'phone_number[]': '1234567',
    'delivery_method': 'pickup', 'privacy_accepted': '1'})
assert array_input[0] != 500 and sql('SELECT COUNT(*) FROM orders') == '0'
csrf_bad = a.post('/plaseaza-comanda', data(key), csrf=False)
print('missing csrf', csrf_bad[0], csrf_bad[1])
assert csrf_bad[0] == 400

fields = data(key, additional_information='<script>alert(1)</script>')
fields['_csrf'] = csrf
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
    jobs = [pool.submit(a.post, '/plaseaza-comanda', fields, False) for _ in range(2)]
    results = [job.result() for job in jobs]
print('concurrent checkout', [(r[0], r[1].rsplit('/', 1)[-1]) for r in results])
print('rows', sql('SELECT COUNT(*) FROM orders'), sql('SELECT COUNT(*) FROM order_items'),
      sql('SELECT COUNT(*) FROM order_email_outbox'), sql('SELECT COUNT(*) FROM cart_item'))
assert all(r[0] == 200 and '/comanda/' in r[1] for r in results)
assert sql('SELECT COUNT(*) FROM orders') == '1'
assert sql('SELECT COUNT(*) FROM order_items') == '1'
assert sql('SELECT COUNT(*) FROM order_email_outbox') == '2'
assert sql('SELECT COUNT(*) FROM cart_item') == '0'
assert sql("SELECT status,subtotal,total FROM orders") == 'pending\t600.00\t600.00'
assert sql("SELECT quantity,unit_price,line_total FROM order_items") == '2\t300.00\t600.00'
assert sql('SELECT product_image FROM order_items') == 'products/buchete/buchete-1-1.png'
number = sql('SELECT order_number FROM orders')
assert '&lt;script&gt;' not in results[0][2]  # Confirmation shows no notes.
assert a.get('/comanda/' + number)[0] == 200
b = Visitor()
try:
    b.get('/comanda/' + number)
    raise AssertionError('Cross-session order access succeeded')
except urllib.error.HTTPError as e:
    assert e.code == 404
print('cross-session confirmation HTTP=404')
reused = b.post('/plaseaza-comanda', data(key, email='intruder@example.invalid', phone='+37360000555'))
print('cross-session key reuse', reused[0])
assert reused[0] == 422 and sql('SELECT COUNT(*) FROM orders') == '1'

assert a.post('/adauga-in-cos', {'product_id': '2', 'quantity': '1'})[0] == 200
second_key, _ = checkout(a)
second = a.post('/plaseaza-comanda', data(second_key))
assert second[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '2'

# Two distinct sessions and keys for the same customer compete for the last
# slot, even if both read their carts before the quota lock.
contenders = []
for n in (2, 3):
    visitor = Visitor()
    assert visitor.post('/adauga-in-cos', {'product_id': '3', 'quantity': '1'})[0] == 200
    contender_key, contender_csrf = checkout(visitor)
    contender_fields = data(contender_key, _csrf=contender_csrf)
    contenders.append((visitor, contender_fields))
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
    jobs = [pool.submit(v.post, '/plaseaza-comanda', fields, False)
            for v, fields in contenders]
    race = [job.result() for job in jobs]
print('two orders then concurrent checkouts', [r[0] for r in race])
assert sorted(r[0] for r in race) == [200, 429]
assert all(int(r[3]['Retry-After']) > 0 for r in race if r[0] == 429)
assert sql('SELECT COUNT(*) FROM orders') == '3'
assert sql('SELECT COUNT(*) FROM order_items') == '3'
assert sql('SELECT COUNT(*) FROM order_email_outbox') == '6'
assert sql('SELECT COUNT(*) FROM order_rate_limit_event') == '6'
assert sql('SELECT COUNT(*) FROM cart_item') == '1'

blocked_visitor, blocked_fields = next((v, fields) for (v, fields), r in zip(contenders, race) if r[0] == 429)
blocked = blocked_visitor.post('/plaseaza-comanda', blocked_fields, False,
    headers={'X-Forwarded-For': '203.0.113.77'})
print('fourth contact order with spoofed forwarding header', blocked[0])
assert blocked[0] == 429 and int(blocked[3]['Retry-After']) > 0
same_email = blocked_visitor.post('/plaseaza-comanda',
    {**blocked_fields, 'email': 'TEST-OWNED@example.invalid', 'phone_number': '+37360000998'}, False)
same_phone = blocked_visitor.post('/plaseaza-comanda',
    {**blocked_fields, 'email': 'new-email@example.invalid', 'phone_number': '+373 (60) 000-001'}, False)
print('same normalized email, same normalized phone', same_email[0], same_phone[0])
assert all(result[0] == 429 and int(result[3]['Retry-After']) > 0
           for result in (same_email, same_phone))
assert sql('SELECT COUNT(*) FROM orders') == '3'
assert sql('SELECT COUNT(*) FROM order_rate_limit_event') == '6'
assert sql('SELECT COUNT(*) FROM cart_item') == '1'

# A distinct customer at the same public IP has a separate successful-order quota.
neighbor = Visitor()
assert neighbor.post('/adauga-in-cos', {'product_id': '3', 'quantity': '1'})[0] == 200
neighbor_key, _ = checkout(neighbor)
neighbor_result = neighbor.post('/plaseaza-comanda', data(neighbor_key,
    email='neighbor@example.invalid', phone='+37360000999'))
print('different customer on shared IP', neighbor_result[0])
assert neighbor_result[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '4'
assert sql('SELECT COUNT(*) FROM order_rate_limit_event') == '8'

sql('DELETE FROM api_rate_limit')
statuses = [blocked_visitor.post('/plaseaza-comanda', blocked_fields, False)[0] for _ in range(21)]
print('rate limit requests', statuses.count(429), 'counter=', sql('SELECT MAX(request_count) FROM api_rate_limit'))
assert statuses == [429] * 21 and sql('SELECT MAX(request_count) FROM api_rate_limit') == '21'
sql('DELETE FROM api_rate_limit')
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
    jobs = [pool.submit(blocked_visitor.post, '/plaseaza-comanda', blocked_fields, False) for _ in range(25)]
    concurrent_limit = [job.result() for job in jobs]
print('concurrent limit', len(concurrent_limit), 'counter=', sql('SELECT MAX(request_count) FROM api_rate_limit'))
assert all(result[0] == 429 and 'Retry-After' in result[3] for result in concurrent_limit)
assert sql('SELECT MAX(request_count) FROM api_rate_limit') == '25'

sql('DELETE FROM api_rate_limit')
# Expire successful-order events to exercise the rolling-window boundary.
sql('UPDATE order_rate_limit_event SET created_at = created_at - 1801')
fault = Visitor()
assert fault.post('/adauga-in-cos', {'product_id': '2', 'quantity': '1'})[0] == 200
fault_key, _ = checkout(fault)
fault_data = data(fault_key, email='rollback@example.invalid', phone='+37360000888')
before_orders = sql('SELECT COUNT(*) FROM orders')
before_events = int(sql('SELECT COUNT(*) FROM order_rate_limit_event'))
before_items = sql('SELECT COUNT(*) FROM order_items')
before_outbox = sql('SELECT COUNT(*) FROM order_email_outbox')
before_cart_items = sql('SELECT COUNT(*) FROM cart_item')
sql("CREATE TRIGGER checkout_security_fail BEFORE INSERT ON order_rate_limit_event FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated rollback test'")
try:
    failed = fault.post('/plaseaza-comanda', fault_data)
    print('forced rollback', failed[0], 'orders=', sql('SELECT COUNT(*) FROM orders'),
          'events=', sql('SELECT COUNT(*) FROM order_rate_limit_event'))
    assert failed[0] == 500
    assert sql('SELECT COUNT(*) FROM orders') == before_orders
    assert int(sql('SELECT COUNT(*) FROM order_rate_limit_event')) == before_events
    assert sql('SELECT COUNT(*) FROM order_items') == before_items
    assert sql('SELECT COUNT(*) FROM order_email_outbox') == before_outbox
    assert sql('SELECT COUNT(*) FROM cart_item') == before_cart_items
finally:
    sql('DROP TRIGGER checkout_security_fail')
recovered = fault.post('/plaseaza-comanda', fault_data)
print('retry after rollback', recovered[0])
assert recovered[0] == 200 and sql('SELECT COUNT(*) FROM orders') == '5'
assert int(sql('SELECT COUNT(*) FROM order_rate_limit_event')) == before_events + 2
print('ALL CHECKS PASSED')
