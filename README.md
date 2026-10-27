# Cifra Ivo Company

Plataforma pessoal de cifras, repertórios e PDFs. Laravel 13 + Blade + Tailwind 4 + Alpine.js + MySQL.

## Rodar localmente (Docker do webServer)

```bash
# na raiz do webServer
docker compose up -d php db

# na pasta do projeto (src/cifras)
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # ajuste DB_* / APP_URL / ASSET_URL
docker compose -f ../../docker-compose.yml exec -w /var/www/html/src/cifras php php artisan migrate
docker compose -f ../../docker-compose.yml exec -w /var/www/html/src/cifras php php artisan cifra:create-admin
docker compose -f ../../docker-compose.yml exec -w /var/www/html/src/cifras php php artisan db:seed   # tags + demo (local)
```

Acesse `http://ivocompany.local:8000/src/cifras`.

Testes (MySQL `cifras_test`): `docker compose -f ../../docker-compose.yml exec -w /var/www/html/src/cifras php php artisan test`.

## Formato das cifras

- Texto puro: acordes numa linha acima da letra, alinhados por espaços. Seções: `[Refrão]`.
- ChordPro: `[C]Letra com [G]acordes`, `{title: …}`, `{key: G}`, `{soc}` / `{eoc}`.

A transposição é calculada na exibição a partir do conteúdo original, que nunca é alterado.

## Deploy

Ver [docs/deploy-hostinger.md](docs/deploy-hostinger.md).
