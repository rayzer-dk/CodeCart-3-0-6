"""Exercise the distributed .htaccess with a real, isolated Apache server."""
import getpass
import grp
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request


root = Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory(prefix='codecart-apache-') as directory:
    fixture = Path(directory)
    web = fixture / 'web'
    web.mkdir()
    shutil.copyfile(root / 'upload/.htaccess', web / '.htaccess')
    for name, value in {'index.php': 'ROUTED', 'robots.txt': 'ROBOTS', 'theme.css': 'CSS',
                        '.env': 'SECRET', '.git/config': 'SECRET',
                        'system/storage/secret.json': 'SECRET', 'backup.sql': 'SECRET',
                        '.well-known/acme-challenge/token_123': 'ACME'}.items():
        target = web / name
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(value)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    modules = ['mpm_event', 'authz_core', 'authz_host', 'dir', 'mime', 'rewrite', 'headers', 'expires']
    config = '\n'.join('LoadModule ' + m + '_module /usr/lib/apache2/modules/mod_' + m + '.so' for m in modules)
    config += f'''
ServerRoot "{fixture}"
ServerName localhost
Listen 127.0.0.1:{port}
PidFile "{fixture}/apache.pid"
ErrorLog "{fixture}/error.log"
User {getpass.getuser()}
Group {grp.getgrgid(os.getgid()).gr_name}
DocumentRoot "{web}"
TypesConfig /etc/mime.types
DirectoryIndex index.php
<Directory "{web}">
AllowOverride All
Require all granted
</Directory>
'''
    path = fixture / 'apache.conf'
    path.write_text(config)
    subprocess.run(['/usr/sbin/apache2', '-t', '-f', str(path)], check=True)
    process = subprocess.Popen(['/usr/sbin/apache2', '-f', str(path), '-DFOREGROUND'], stdout=subprocess.DEVNULL, stderr=subprocess.PIPE)
    def request(url):
        try:
            with urllib.request.urlopen(f'http://127.0.0.1:{port}/' + url, timeout=3) as response:
                return response.status, response.read().decode()
        except urllib.error.HTTPError as error:
            return error.code, error.read().decode()
    try:
        for _ in range(50):
            try:
                request('robots.txt')
                break
            except OSError:
                time.sleep(.1)
        checks = {'.env': (403, None), '.git/config': (403, None),
                  'system/storage/secret.json': (403, None), 'backup.sql': (403, None),
                  '.well-known/acme-challenge/token_123': (200, 'ACME'),
                  'robots.txt': (200, 'ROBOTS'), 'theme.css': (200, 'CSS'),
                  'lost-product': (200, 'ROUTED'), 'sitemap.xml': (200, 'ROUTED'),
                  'ua/sitemap-products-uk-ua-1.xml': (200, 'ROUTED')}
        for url, (status, body) in checks.items():
            actual_status, actual_body = request(url)
            assert actual_status == status and (body is None or actual_body == body), (url, actual_status, actual_body)
            print('PASS', url, status)
    finally:
        process.terminate()
        process.communicate(timeout=10)
