"""Run only against a disposable local MySQL server and database."""
import concurrent.futures
import http.cookiejar
import json
import os
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('CART_TEST_URL', 'http://127.0.0.1:18765')
SOCKET = os.environ.get('CART_TEST_SOCKET', '')
DATABASE = os.environ.get('CART_TEST_DATABASE', '')
assert BASE.startswith('http://127.0.0.1:')
assert SOCKET.startswith('/private/tmp/anna-cart-mysql.') and SOCKET.endswith('/mysql.sock')
assert DATABASE == 'anna_cart_security_test'

def mysql(statement):
    cmd = ['/usr/local/mysql/bin/mysql', '--no-defaults', '--protocol=socket',
           '--socket=' + SOCKET, '-u', 'root', '--batch', '--skip-column-names',
           DATABASE, '-e', statement]
    return subprocess.run(cmd, capture_output=True, text=True)

def sql(statement):
    result = mysql(statement)
    assert result.returncode == 0, result.stderr
    return result.stdout.strip()

identity = sql('SELECT @@datadir, DATABASE(), @@skip_networking')
assert identity.startswith(os.path.dirname(SOCKET) + '/\t' + DATABASE + '\t1'), identity
if os.environ.get('CART_TEST_RESET') == '1':
    sql('DELETE FROM cart')
    sql('DELETE FROM api_rate_limit')
assert sql('SELECT COUNT(*) FROM cart') == '0', 'Test database must start with empty carts'

def visitor():
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    page = opener.open(BASE + '/').read().decode()
    token = re.search(r'name="_csrf" value="([^"]+)"', page).group(1)
    cookie = '; '.join(f'{c.name}={c.value}' for c in jar)
    session = next(c.value for c in jar if c.name == 'PHPSESSID')
    return cookie, token, session

def call(method, path, visitor_data, fields):
    cookie, token, _ = visitor_data
    body = urllib.parse.urlencode(dict(fields, _csrf=token)).encode()
    request = urllib.request.Request(BASE + path, body, method=method,
        headers={'Cookie': cookie, 'Accept': 'application/json',
                 'Content-Type': 'application/x-www-form-urlencoded'})
    try:
        response = urllib.request.urlopen(request, timeout=20)
    except urllib.error.HTTPError as error:
        response = error
    try:
        payload = json.loads(response.read())
    except (ValueError, UnicodeDecodeError):
        payload = {}
    return response.status, payload

def burst(count, method, path, who, fields):
    with concurrent.futures.ThreadPoolExecutor(max_workers=count) as pool:
        jobs = [pool.submit(call, method, path, who, fields) for _ in range(count)]
        return [job.result() for job in jobs]

def report(label, results, db):
    print(label, 'HTTP=' + str([status for status, _ in results]), 'DB=' + db)

a = visitor()
results = burst(8, 'POST', '/adauga-in-cos', a, {'product_id': 1, 'quantity': 1})
report('new-cart concurrent POST', results, sql("SELECT COUNT(*), COALESCE(SUM(ci.quantity),0) FROM cart c JOIN cart_item ci ON ci.cart_id=c.id WHERE c.session_id='%s'" % a[2]))
cart_id, item_id, quantity = sql("SELECT c.id, ci.id, ci.quantity FROM cart c JOIN cart_item ci ON ci.cart_id=c.id WHERE c.session_id='%s'" % a[2]).split('\t')
assert all(code == 200 for code, _ in results) and quantity == '8'
assert sorted(payload['cartCount'] for _, payload in results) == list(range(1, 9))

results = burst(4, 'POST', '/adauga-in-cos', a, {'product_id': 1, 'quantity': 1})
report('maximum quantity POST', results, sql('SELECT quantity FROM cart_item WHERE id=' + item_id))
assert sql('SELECT quantity FROM cart_item WHERE id=' + item_id) == '10'

sql('DELETE FROM api_rate_limit')
results = []
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
    jobs = [pool.submit(call, 'PUT', '/actualizeaza-cos', a,
                        {'cart_item_id': item_id, 'quantity': i}) for i in range(2, 10)]
    results = [job.result() for job in jobs]
report('concurrent PUT', results, sql('SELECT quantity FROM cart_item WHERE id=' + item_id))
assert all(code == 200 for code, _ in results)
assert 2 <= int(sql('SELECT quantity FROM cart_item WHERE id=' + item_id)) <= 9
price = float(sql('SELECT price FROM products WHERE id=1'))
for _, payload in results:
    assert payload['cartCount'] == payload['quantity']
    assert abs(payload['total'] - price * payload['quantity']) < 0.01
    assert abs(payload['subtotal'] - payload['total']) < 0.01

b = visitor()
results = [call('PUT', '/actualizeaza-cos', b, {'cart_item_id': item_id, 'quantity': 10}),
           call('DELETE', '/sterge-din-cos', b, {'cart_item_id': item_id})]
report('cross-session ownership', results, sql('SELECT COUNT(*) FROM cart_item WHERE id=' + item_id))
assert [r[0] for r in results] == [404, 404]

sql('DELETE FROM api_rate_limit')
results = burst(5, 'DELETE', '/sterge-din-cos', a, {'cart_item_id': item_id})
report('concurrent DELETE', results, sql('SELECT COUNT(*) FROM cart_item WHERE id=' + item_id))
assert sorted(r[0] for r in results) == [200, 404, 404, 404, 404]
assert sql('SELECT COUNT(*) FROM cart_item WHERE id=' + item_id) == '0'
assert next(payload for code, payload in results if code == 200)['total'] == 0

sql('DELETE FROM api_rate_limit')
added = call('POST', '/adauga-in-cos', a, {'product_id': 1, 'quantity': 10})
over = call('POST', '/adauga-in-cos', a, {'product_id': 1, 'quantity': 1})
invalid = call('PUT', '/actualizeaza-cos', a, {'cart_item_id': item_id, 'quantity': 11})
print('max and rejected writes', 'HTTP=' + str([added[0], over[0], invalid[0]]),
      'DB=' + sql('SELECT COUNT(*), SUM(quantity) FROM cart_item WHERE cart_id=' + cart_id + ' AND product_id=1'))
assert [added[0], over[0], invalid[0]] == [200, 422, 422]
assert sql('SELECT COUNT(*), SUM(quantity) FROM cart_item WHERE cart_id=' + cart_id + ' AND product_id=1') == '1\t10'
assert mysql("INSERT INTO cart(session_id,created_at,updated_at) VALUES ('%s',1,1)" % a[2]).returncode != 0
assert mysql('INSERT INTO cart_item(cart_id,product_id,quantity) VALUES (%s,1,1)' % cart_id).returncode != 0
print('unique constraints', 'duplicate cart=blocked', 'duplicate item=blocked')

lock = subprocess.Popen(['/usr/local/mysql/bin/mysql', '--no-defaults', '--protocol=socket',
    '--socket=' + SOCKET, '-u', 'root', DATABASE, '-e',
    'START TRANSACTION; SELECT id FROM cart WHERE id=%s FOR UPDATE; SELECT SLEEP(2); COMMIT' % cart_id],
    stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
time.sleep(0.3)
start = time.monotonic()
sql('UPDATE cart SET updated_at=updated_at WHERE id=' + cart_id)
blocked = time.monotonic() - start
lock.wait(timeout=10)
print('InnoDB FOR UPDATE', 'blocked_seconds=' + str(round(blocked, 2)))
assert lock.returncode == 0 and blocked >= 1.4

# Force the cart timestamp write to fail after the item insert. Both writes must roll back.
sql('UPDATE cart SET updated_at=0 WHERE id=' + cart_id)
sql("CREATE TRIGGER cart_security_fail BEFORE UPDATE ON cart FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='isolated rollback test'")
try:
    sql('DELETE FROM api_rate_limit')
    failed = call('POST', '/adauga-in-cos', a, {'product_id': 3, 'quantity': 1})
    remaining = sql('SELECT COUNT(*) FROM cart_item WHERE cart_id=' + cart_id + ' AND product_id=3')
    timestamp = sql('SELECT updated_at FROM cart WHERE id=' + cart_id)
    print('forced transaction rollback', 'HTTP=' + str(failed[0]), 'new_items=' + remaining, 'timestamp=' + timestamp)
    assert failed[0] == 500 and remaining == '0' and timestamp == '0'
finally:
    sql('DROP TRIGGER cart_security_fail')

sql('DELETE FROM api_rate_limit')
c = visitor()
results = burst(14, 'POST', '/adauga-in-cos', c, {'product_id': 2, 'quantity': 1})
report('concurrent rate limit', results, sql("SELECT COUNT(*), COALESCE(SUM(ci.quantity),0) FROM cart c JOIN cart_item ci ON ci.cart_id=c.id WHERE c.session_id='%s'" % c[2]))
assert sorted(r[0] for r in results) == [200] * 10 + [429] * 4
assert sql("SELECT request_count FROM api_rate_limit WHERE request_count>=14") == '14'
assert sql("SELECT SUM(ci.quantity), ROUND(SUM(ci.quantity*p.price),2) FROM cart c JOIN cart_item ci ON ci.cart_id=c.id JOIN products p ON p.id=ci.product_id WHERE c.session_id='%s'" % c[2]).split('\t')[0] == '10'

print('ALL ASSERTIONS PASSED')
