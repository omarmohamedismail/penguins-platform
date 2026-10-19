"""Database-free checks against an ephemeral local PHP server."""
import os
from pathlib import Path
import socket
import subprocess
import time
import urllib.request
import urllib.error
import re
import tempfile

root = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', 'php')
for source in root.rglob('*.php'):
    subprocess.run([php, '-l', str(source)], check=True, capture_output=True)
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
sessions = tempfile.TemporaryDirectory(prefix='penguins-test-', dir=root / 'tests')
server = subprocess.Popen([php, '-d', 'session.save_path=' + sessions.name, '-S', f'127.0.0.1:{port}', '-t', str(root)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
base = f'http://127.0.0.1:{port}/'
def request(path, method='GET', data=None, headers=None):
    req = urllib.request.Request(base + path, data=data, method=method, headers=headers or {})
    try:
        return urllib.request.urlopen(req, timeout=5)
    except urllib.error.HTTPError as error:
        return error
try:
    for _ in range(50):
        try:
            response = request('auth.php')
            break
        except urllib.error.URLError:
            time.sleep(.1)
    else:
        raise RuntimeError('PHP server failed to start')
    html = response.read().decode()
    csrf = re.search(r'data-csrf="([a-f0-9]+)"', html).group(1)
    cookie = response.headers['Set-Cookie'].split(';')[0]
    headers = {'Cookie': cookie, 'X-CSRF-Token': csrf, 'Content-Type': 'application/json'}
    assert request('login.php').code == 405
    assert request('get_data.php').code == 401
    assert request('save_data.php', 'POST', b'{}').code == 401
    assert request('login.php', 'POST', b'{}').code == 403
    assert request('logout.php').code == 405
    for endpoint in ['login.php', 'register.php']:
        for body in [b'null', b'[]', b'1', b'{']:
            response = request(endpoint, 'POST', body, headers)
            assert response.code == 400, (endpoint, body, response.code, response.read())
        assert request(endpoint, 'POST', b'{"email":[]}', headers).code == 422
        assert request(endpoint, 'POST', b'{}', {**headers, 'Content-Type': 'text/plain'}).code == 415
    assert request('login.php', 'POST', b'{' + b' ' * 5001, headers).code == 413
    assert 'auth.html' not in (root / 'index.html').read_text(encoding='utf-8')
    print('PASS: PHP lint and database-free API smoke checks')
finally:
    server.terminate()
    server.wait(timeout=10)
    sessions.cleanup()
