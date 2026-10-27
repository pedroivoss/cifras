# Deploy — Hostinger VPS (cifra.ivocompany.com)

> **Verifique antes de seguir.** Este guia assume a VPS Hostinger com **CloudPanel + Ubuntu 24.04**
> (Nginx + PHP-FPM + MySQL/MariaDB, acesso SSH). Não tive acesso ao servidor: confirme os itens da
> seção 1. Em hospedagem compartilhada (hPanel, sem root/Nginx próprio), veja a seção 8.

## 1. Pré-requisitos no servidor

```bash
php -v                  # >= 8.3 (o projeto foi desenvolvido e testado em 8.5)
php -m | grep -Ei 'pdo_mysql|mbstring|dom|gd|xml|fileinfo|openssl|tokenizer|ctype|curl'
composer -V
mysql --version
node -v                 # só se for compilar os assets no servidor (>= 20)
```

Extensões obrigatórias: `pdo_mysql`, `mbstring`, `dom`, `xml`, `gd` (Dompdf), `fileinfo`, `openssl`, `ctype`, `tokenizer`, `curl`.

## 2. Site e banco no CloudPanel

1. **Sites → Add Site → Create a PHP Site**: domínio `cifra.ivocompany.com`, versão PHP 8.3+ (a mais recente disponível), vhost template **Laravel**.
2. **Databases → Add Database**: banco `cifras`, usuário próprio com senha forte (só para este banco).
3. **SSL/TLS → Actions → New Let's Encrypt Certificate** (após o DNS do subdomínio apontar para a VPS).

O template Laravel do CloudPanel já aponta o `root` para `.../htdocs/cifra.ivocompany.com/public`. A
configuração equivalente, caso precise conferir ou montar à mão, está na seção 7.

## 3. Código

```bash
# como o usuário do site (ex.: su - ivocompany-cifra)
cd ~/htdocs/cifra.ivocompany.com
git clone <repositório> .            # ou rsync/scp do projeto (sem node_modules, vendor, .env)
composer install --no-dev --optimize-autoloader
```

Assets (escolha um):

- **No servidor**: `npm ci && npm run build`
- **Na máquina local**, sem o `ASSET_URL` de desenvolvimento: `ASSET_URL= npm run build` e envie `public/build/`.

> O `.env` local usa `ASSET_URL=http://ivocompany.local:8000/src/cifras`; um build feito com ele embute esse
> endereço nas fontes do CSS. Em produção o `ASSET_URL` deve ficar vazio/ausente.

## 4. `.env` de produção

```bash
cp .env.example .env
php artisan key:generate
```

Ajuste:

```dotenv
APP_NAME="Cifra Ivo Company"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cifra.ivocompany.com
APP_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cifras
DB_USERNAME=<usuário do banco>
DB_PASSWORD=<senha do banco>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true

CIFRACLUB_API_URL=          # vazio = importação externa desativada
```

`chmod 600 .env`.

## 5. Banco, admin e otimizações

```bash
php artisan migrate --force
php artisan db:seed --class=TagSeeder --force   # tags iniciais (o DemoSeeder só roda em local)
php artisan cifra:create-admin                  # pede e-mail, nome e senha (mín. 10) de forma interativa
php artisan optimize                            # config, rotas, views e eventos em cache
```

Permissões (usuário do site = dono; PHP-FPM do CloudPanel roda com esse mesmo usuário):

```bash
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Não há filas, cron nem arquivos enviados permanentes: PDFs são gerados sob demanda e não ficam salvos.

## 6. Atualizações

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build        # se compilar no servidor
php artisan migrate --force
php artisan optimize
php artisan up
```

## 7. Nginx (referência)

O CloudPanel gerencia o vhost; use isto para conferir o que ele gerou. Pontos essenciais: `root` em `public/`,
`try_files` para o `index.php`, bloqueio de arquivos ocultos e `fastcgi` para o PHP-FPM do site.

```nginx
server {
    listen 443 ssl;
    http2 on;
    server_name cifra.ivocompany.com;
    root /home/<usuario>/htdocs/cifra.ivocompany.com/public;
    index index.php;

    # ssl_certificate / ssl_certificate_key: gerenciados pelo CloudPanel (Let's Encrypt)

    client_max_body_size 2m;            # import de cifra é limitado a 512 KB
    charset utf-8;

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known) { deny all; }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass 127.0.0.1:<porta-do-php-fpm-do-site>;   # o CloudPanel define a porta por site
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;      # PDFs grandes podem levar alguns segundos
    }

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
}

server {
    listen 80;
    server_name cifra.ivocompany.com;
    return 301 https://$host$request_uri;
}
```

Os arquivos `server.php` e `.htaccess` na raiz do projeto são só o shim de desenvolvimento do Apache
local (`ivocompany.local:8000/src/cifras`). Com o `root` em `public/` eles nunca são servidos.

## 8. Se for hospedagem compartilhada (hPanel)

- Sem Nginx próprio: o servidor é LiteSpeed/Apache. Aponte o subdomínio para `public/` no hPanel
  (Domínios → Subdomínios → pasta de destino). Se não for possível, mantenha o projeto fora de
  `public_html` e mova só o conteúdo de `public/` para a pasta do subdomínio, ajustando os caminhos em `index.php`.
- Sem Node no servidor: compile os assets localmente (`ASSET_URL= npm run build`).
- Confirme a versão do PHP e as extensões no hPanel antes do upload.

## 9. Backup do banco

Script simples (crontab do usuário, diário às 03:00, mantém 14 dias):

```bash
# ~/bin/backup-cifras.sh
#!/usr/bin/env bash
set -euo pipefail
DIR="$HOME/backups/cifras"
mkdir -p "$DIR"
mysqldump --single-transaction --no-tablespaces -u <usuario> -p'<senha>' cifras | gzip > "$DIR/cifras-$(date +%F).sql.gz"
find "$DIR" -name 'cifras-*.sql.gz' -mtime +14 -delete
```

```cron
0 3 * * * $HOME/bin/backup-cifras.sh
```

Prefira um arquivo `~/.my.cnf` (`chmod 600`) com as credenciais em vez de senha na linha de comando.
Copie periodicamente os backups para fora da VPS. O CloudPanel também tem backup remoto em **Admin Area → Backups**.

Restaurar: `gunzip < cifras-AAAA-MM-DD.sql.gz | mysql -u <usuario> -p cifras`.

## 10. Checklist pós-deploy

- [ ] `https://cifra.ivocompany.com` abre o login (sem página inicial pública).
- [ ] `https://cifra.ivocompany.com/.env` retorna 404.
- [ ] `APP_DEBUG=false` (um erro proposital mostra página genérica, sem stack trace).
- [ ] Login, criação de playlist e download de PDF funcionando.
- [ ] Link público `/p/{token}` abre sem login e responde com `X-Robots-Tag: noindex, nofollow`.
