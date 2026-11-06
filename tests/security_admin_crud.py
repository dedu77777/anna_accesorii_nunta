"""Admin CRUD integration checks. Requires a fresh private MySQL test instance."""
import base64
import http.cookiejar
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ['ADMIN_TEST_URL']
SOCKET = os.environ['ADMIN_TEST_SOCKET']
DB = 'anna_admin_crud_test'
assert BASE.startswith('http://127.0.0.1:')
assert SOCKET.startswith('/private/tmp/anna-admin-mysql.') and SOCKET.endswith('/mysql.sock')


def sql(query):
    result = subprocess.run([
        '/usr/local/mysql/bin/mysql', '--no-defaults', '--protocol=socket',
        '--socket=' + SOCKET, '-uroot', '--batch', '--skip-column-names', DB, '-e', query,
    ], capture_output=True, text=True, check=True)
    return result.stdout.strip()


assert sql('SELECT @@datadir, @@skip_networking').startswith(str(Path(SOCKET).parent / 'data') + '/\t1')
assert sql("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('products','product_images','category') AND ENGINE='InnoDB'") == '3'
print('isolated MySQL: private data directory, network disabled, three InnoDB catalog tables')
sql('DELETE FROM api_rate_limit')  # Private test server only; avoids the login cap across repeated suite runs.

PNG = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')


class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, fields=None, files=None, method='GET', csrf=True):
        fields = dict(fields or {})
        if method == 'POST' and csrf:
            source = '/admin/products' if '/products/' in path and path.endswith('/delete') else ('/admin/categories' if path.endswith('/delete') else path)
            fields['_csrf'] = self.csrf(source)
        if files:
            boundary = 'anna-admin-test-boundary'
            parts = []
            for name, value in fields.items():
                parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n').encode())
            for name, filename, data, mime in files:
                parts.append((f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n').encode() + data + b'\r\n')
            parts.append(f'--{boundary}--\r\n'.encode())
            body = b''.join(parts)
            headers = {'Content-Type': f'multipart/form-data; boundary={boundary}'}
        else:
            body = urllib.parse.urlencode(fields).encode() if method == 'POST' else None
            headers = {}
        request = urllib.request.Request(BASE + path, body, headers=headers, method=method)
        try:
            with self.opener.open(request) as response:
                return response.status, response.geturl(), response.read().decode()
        except urllib.error.HTTPError as error:
            return error.code, error.geturl(), error.read().decode()

    def csrf(self, path):
        page = self.request(path)[2]
        match = re.search(r'name="_csrf" value="([^"]+)"', page)
        assert match, (path, page[:250])
        return match.group(1)


client = Client()
guest = client.request('/admin/products')
assert guest[0] == 200 and guest[1].endswith('/admin-anna-accesorii')
login = client.request('/admin-anna-accesorii', {
    'AdminLoginForm[email]': 'admin-crud-test@example.invalid',
    'AdminLoginForm[password]': 'IsolatedCrudTest!2026',
}, method='POST')
assert login[0] == 200 and login[1].endswith('/admin/dashboard-anna-accesorii'), login[:2]
assert client.request('/admin/products')[0] == 200
assert client.request('/admin/categories')[0] == 200
print('guest redirect, admin login, product/category lists: HTTP 200')
stranger = Client()
blocked_guest = stranger.request('/admin/categories/create', {'CategoryForm[title]': 'Guest attempt'}, [('CategoryForm[image]', 'guest.png', PNG, 'image/png')], 'POST')
assert blocked_guest[0] == 200 and blocked_guest[1].endswith('/admin-anna-accesorii')
assert sql("SELECT COUNT(*) FROM category WHERE title='Guest attempt'") == '0'

initial_products = int(sql('SELECT COUNT(*) FROM products'))
initial_categories = int(sql('SELECT COUNT(*) FROM category'))


def image_files(count):
    return [('ProductForm[images][]', f'photo-{i}.png', PNG, 'image/png') for i in range(count)]


def product_fields(category_id, suffix):
    return {
        'ProductForm[title]': f'Admin test produs {suffix}',
        'ProductForm[subtitle]': f'Subtitlu {suffix}',
        'ProductForm[description]': 'Descriere completă pentru test.',
        'ProductForm[price]': '25.50',
        'ProductForm[category_id]': str(category_id),
    }

category = client.request('/admin/categories/create', {
    'CategoryForm[title]': 'Categorie test admin',
}, [('CategoryForm[image]', 'category.png', PNG, 'image/png')], 'POST')
assert category[0] == 200 and category[1].endswith('/admin/categories'), category[:2]
category_id = sql("SELECT MAX(id) FROM category WHERE title='Categorie test admin'")
assert category_id.isdigit() and int(sql('SELECT COUNT(*) FROM category')) == initial_categories + 1
category_image = sql(f'SELECT image FROM category WHERE id={category_id}')
assert category_image.startswith('admin-') and (ROOT / 'web/images/categories' / category_image).exists()
print('category create: one image, database row and file')

created = []
for count in (1, 2, 3):
    result = client.request('/admin/products/create', product_fields(category_id, count), image_files(count), 'POST')
    assert result[0] == 200 and result[1].endswith('/admin/products'), (count, result[:2], result[2][:250])
    product_id = sql(f"SELECT MAX(id) FROM products WHERE title='Admin test produs {count}'")
    assert product_id.isdigit()
    images = sql(f'SELECT image FROM product_images WHERE product_id={product_id} ORDER BY sort_order,id').splitlines()
    assert len(images) == count and all((ROOT / 'web/images' / path).exists() for path in images)
    assert sql(f'SELECT image FROM products WHERE id={product_id}') == images[0]
    assert sql(f'SELECT subtitle FROM products WHERE id={product_id}') == f'Subtitlu {count}'
    created.append((product_id, images))
print('product create: 1, 2, 3 images, ordered gallery, primary image, subtitle')

for suffix, fields, files in [
    ('four', product_fields(category_id, 'four'), image_files(4)),
    ('invalid-type', product_fields(category_id, 'invalid-type'), [('ProductForm[images][]', 'bad.jpg', b'<script>x</script>', 'image/jpeg')]),
    ('invalid-category', product_fields(99999999, 'invalid-category'), image_files(1)),
]:
    result = client.request('/admin/products/create', fields, files, 'POST')
    assert result[0] == 200 and result[1].endswith('/admin/products/create'), (suffix, result[:2])
    assert sql(f"SELECT COUNT(*) FROM products WHERE title='Admin test produs {suffix}'") == '0'
print('invalid create: four images, spoofed image, unknown category; zero product rows')

product_id, original_images = created[2]
edit_path = f'/admin/products/{product_id}/edit'
fields = product_fields(category_id, 'three')
fields['ProductForm[title]'] = 'Admin test produs actualizat'
fields['ProductForm[price]'] = '32.75'
preserved = client.request(edit_path, fields, method='POST')
assert preserved[0] == 200 and preserved[1].endswith('/admin/products')
assert sql(f'SELECT title,price FROM products WHERE id={product_id}') == 'Admin test produs actualizat\t32.75'
assert sql(f'SELECT image FROM product_images WHERE product_id={product_id} ORDER BY sort_order,id').splitlines() == original_images
print('product update: fields changed, images preserved')

remove_id = sql(f'SELECT id FROM product_images WHERE product_id={product_id} ORDER BY sort_order,id LIMIT 1')
fields['ProductForm[removeImageIds][]'] = remove_id
replaced = client.request(edit_path, fields, image_files(1), 'POST')
assert replaced[0] == 200 and replaced[1].endswith('/admin/products')
new_images = sql(f'SELECT image FROM product_images WHERE product_id={product_id} ORDER BY sort_order,id').splitlines()
assert len(new_images) == 3 and original_images[0] not in new_images
assert sql(f'SELECT image FROM products WHERE id={product_id}') == new_images[0]
assert not (ROOT / 'web/images' / original_images[0]).exists()
print('product update: one removed, one added, max three, old managed file cleaned')

fields = product_fields(category_id, 'three')
excess = client.request(edit_path, fields, image_files(1), 'POST')
assert excess[1].endswith(edit_path) and sql(f'SELECT COUNT(*) FROM product_images WHERE product_id={product_id}') == '3'
print('product update: fourth retained/new image rejected')

legacy_id, legacy_images = created[0]
sql(f'DELETE FROM product_images WHERE product_id={legacy_id}')
legacy_fields = product_fields(category_id, 'one')
legacy_fields['ProductForm[removeLegacyImage]'] = '1'
legacy_replaced = client.request(f'/admin/products/{legacy_id}/edit', legacy_fields, image_files(1), 'POST')
assert legacy_replaced[0] == 200 and legacy_replaced[1].endswith('/admin/products')
legacy_new = sql(f'SELECT image FROM product_images WHERE product_id={legacy_id}')
assert legacy_new.startswith('products/admin/') and legacy_new != legacy_images[0]
assert sql(f'SELECT image FROM products WHERE id={legacy_id}') == legacy_new
assert not (ROOT / 'web/images' / legacy_images[0]).exists()
print('legacy product image: replacement creates gallery row and removes old managed file')

two_id, two_images = created[1]
remove_all_fields = product_fields(category_id, 'two')
remove_all_fields['ProductForm[removeImageIds][]'] = two_images[0]  # replaced with IDs below
remove_ids = sql(f'SELECT id FROM product_images WHERE product_id={two_id} ORDER BY id').splitlines()
remove_all_fields.pop('ProductForm[removeImageIds][]')
remove_all_fields['ProductForm[removeImageIds][0]'] = remove_ids[0]
remove_all_fields['ProductForm[removeImageIds][1]'] = remove_ids[1]
remove_all = client.request(f'/admin/products/{two_id}/edit', remove_all_fields, method='POST')
assert remove_all[0] == 200 and remove_all[1].endswith(f'/admin/products/{two_id}/edit')
assert sql(f'SELECT COUNT(*) FROM product_images WHERE product_id={two_id}') == '2'
print('product update: removing every image without replacement rejected')

category_edit = client.request(f'/admin/categories/{category_id}/edit', {'CategoryForm[title]': 'Categorie test actualizată'}, method='POST')
assert category_edit[0] == 200 and category_edit[1].endswith('/admin/categories')
assert sql(f'SELECT image FROM category WHERE id={category_id}') == category_image
category_replace = client.request(f'/admin/categories/{category_id}/edit', {'CategoryForm[title]': 'Categorie test actualizată'}, [('CategoryForm[image]', 'replacement.png', PNG, 'image/png')], 'POST')
assert category_replace[0] == 200 and category_replace[1].endswith('/admin/categories')
replacement_image = sql(f'SELECT image FROM category WHERE id={category_id}')
assert replacement_image != category_image and (ROOT / 'web/images/categories' / replacement_image).exists()
assert not (ROOT / 'web/images/categories' / category_image).exists()
print('category update: title, preserve image, replace image, old file cleaned')

blocked = client.request(f'/admin/categories/{category_id}/delete', method='POST')
assert blocked[0] == 200 and sql(f'SELECT COUNT(*) FROM category WHERE id={category_id}') == '1'
assert 'conține produse' in blocked[2]
print('category with products: deletion blocked')

for product_id, _ in created:
    path = f'/admin/products/{product_id}/delete'
    get_result = client.request(path)
    assert get_result[0] in (404, 405)
    paths_to_remove = sql(f'SELECT image FROM product_images WHERE product_id={product_id}').splitlines()
    deleted = client.request(path, method='POST')
    assert deleted[0] == 200 and deleted[1].endswith('/admin/products')
    assert sql(f'SELECT COUNT(*) FROM products WHERE id={product_id}') == '0'
    assert sql(f'SELECT COUNT(*) FROM product_images WHERE product_id={product_id}') == '0'
    assert all(not (ROOT / 'web/images' / image).exists() for image in paths_to_remove)
print('product deletion: POST only, rows and gallery removed')

empty_category = client.request(f'/admin/categories/{category_id}/delete', method='POST')
assert empty_category[0] == 200 and empty_category[1].endswith('/admin/categories')
assert sql(f'SELECT COUNT(*) FROM category WHERE id={category_id}') == '0'
assert not (ROOT / 'web/images/categories' / replacement_image).exists()
print('empty category deletion: row and managed image removed')

for path in ['/admin/products/99999999/edit', '/admin/categories/99999999/edit']:
    assert client.request(path)[0] == 404
for path in ['/admin/products/99999999/delete', '/admin/categories/99999999/delete']:
    assert client.request(path, method='POST')[0] == 404
csrf = client.request('/admin/categories/create', {'CategoryForm[title]': 'CSRF test'}, [('CategoryForm[image]', 'category.png', PNG, 'image/png')], 'POST', csrf=False)
assert csrf[0] == 400
assert sql("SELECT COUNT(*) FROM category WHERE title='CSRF test'") == '0'
assert int(sql('SELECT COUNT(*) FROM products')) == initial_products
assert int(sql('SELECT COUNT(*) FROM category')) == initial_categories
print('missing IDs: HTTP 404; CSRF failure: HTTP 400; database counts restored')

catalog_id = sql('SELECT MIN(id) FROM category')
product_files_before = set((ROOT / 'web/images/products/admin').glob('*'))
sql("CREATE TRIGGER admin_test_fail_product_image BEFORE INSERT ON product_images FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'isolated test failure'")
try:
    failed = client.request('/admin/products/create', product_fields(catalog_id, 'rollback'), image_files(1), 'POST')
    assert failed[0] == 200 and failed[1].endswith('/admin/products/create')
    assert sql("SELECT COUNT(*) FROM products WHERE title='Admin test produs rollback'") == '0'
    assert set((ROOT / 'web/images/products/admin').glob('*')) == product_files_before
finally:
    sql('DROP TRIGGER admin_test_fail_product_image')
print('forced image-record failure: product transaction rolled back and uploaded file removed')

category_files_before = set((ROOT / 'web/images/categories').glob('admin-*'))
sql("CREATE TRIGGER admin_test_fail_category BEFORE INSERT ON category FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'isolated test failure'")
try:
    failed = client.request('/admin/categories/create', {'CategoryForm[title]': 'Categorie rollback test'}, [('CategoryForm[image]', 'category.png', PNG, 'image/png')], 'POST')
    assert failed[0] == 200 and failed[1].endswith('/admin/categories/create')
    assert sql("SELECT COUNT(*) FROM category WHERE title='Categorie rollback test'") == '0'
    assert set((ROOT / 'web/images/categories').glob('admin-*')) == category_files_before
finally:
    sql('DROP TRIGGER admin_test_fail_category')
print('forced category insert failure: no row and uploaded file removed')
