# Deployment (local)

## 1. Plain XAMPP
See *Quick start* in the README. URL: `http://localhost/StudentHub/`.

## 2. Apache virtual host (Practical 15 intermediate)

1. Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and add:

```apache
<VirtualHost *:80>
    ServerName studenthub.local
    DocumentRoot "C:/xampp/htdocs/StudentHub"
    <Directory "C:/xampp/htdocs/StudentHub">
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog  "logs/studenthub-error.log"
    CustomLog "logs/studenthub-access.log" common
</VirtualHost>
```

2. Make sure `httpd.conf` contains `Include conf/extra/httpd-vhosts.conf` (it is enabled by default in XAMPP).
3. Add this line to `C:\Windows\System32\drivers\etc\hosts` (run the editor as Administrator):

```
127.0.0.1   studenthub.local
```

4. Restart Apache and open `http://studenthub.local/`. No code change is needed – `BASE_URL`
   is detected automatically (`""` for a virtual host, `"/StudentHub"` under htdocs).

## 3. Hosting notes (Practical 15 advanced)
* Static part (`css/`, `js/`, `images/`, `data/`) can be hosted on GitHub Pages, but pages are `.php`,
  so the **full app needs PHP + MySQL hosting** (shared hosting, a VPS, or Docker with `php:8-apache` + `mysql`).
* Before going public: change MySQL credentials, create a non-root DB user, delete `test_connection.php`,
  enable HTTPS (the session/remember cookies automatically become `Secure`), and set `display_errors=Off`.
* Backend limitation on free static hosts: no PHP/MySQL → login, CRUD, uploads and the API will not work there.
