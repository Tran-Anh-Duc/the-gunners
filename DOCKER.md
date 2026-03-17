# Huong dan Docker

## Cach chay tren may moi

### Che do local

Dung che do nay khi ban muon app chay voi MySQL local trong Docker.

1. Clone project

```bash
git clone <repo-url>
cd the-gunners
```

2. Tao file moi truong

```bash
cp .env.example .env
```

3. Chay Docker

```bash
docker compose up --build -d
```

4. Nap schema database

```bash
docker compose exec app php artisan migrate
```

5. Seed du lieu mau neu can

```bash
docker compose exec app php artisan db:seed --force
```

6. Mo ung dung

- App: http://localhost:18080
- phpMyAdmin: http://localhost:18081
- MySQL tu may host: `127.0.0.1:13307`

### Che do Aiven ca nhan

Dung che do nay chi tren may cua ban khi muon app trong Docker ket noi truc tiep toi Aiven thay vi MySQL local.

1. Clone project

```bash
git clone <repo-url>
cd the-gunners
```

2. Tao file moi truong tu mau Aiven

```bash
cp .env.aiven.example .env
```

3. Dien dung thong tin Aiven va `JWT_SECRET` trong `.env`

4. Chay Docker voi file override Aiven

```bash
docker compose -f docker-compose.yml -f docker-compose.aiven.yml up --build -d
```

5. Chay cac lenh migrate an toan

```bash
docker compose -f docker-compose.yml -f docker-compose.aiven.yml exec app php artisan migrate
```

6. Mo ung dung

- App: http://localhost:18080
- phpMyAdmin: http://localhost:18081

## Y nghia cac file

- `docker-compose.yml`: file Docker chinh, mac dinh dung MySQL local
- `docker-compose.aiven.yml`: file override, ghi de cau hinh DB cua app bang cac bien trong `.env`
- `.env.example`: file mau cho local MySQL
- `.env.aiven.example`: file mau cho Aiven

## Co che bao ve khi dung Aiven

App se chan cac lenh pha du lieu neu `DB_HOST` dang tro toi Aiven:

- `php artisan migrate:fresh`
- `php artisan migrate:refresh`
- `php artisan migrate:reset`
- `php artisan db:wipe`

Neu ban that su muon bo qua co che chan nay:

```bash
docker compose -f docker-compose.yml -f docker-compose.aiven.yml exec \
  -e ALLOW_DESTRUCTIVE_DB_COMMANDS=true \
  app php artisan migrate:fresh --force
```

Chi dung cach nay khi ban chac chan muon xoa du lieu remote.

## Khi sua code trong Docker

Service `app` da mount cac thu muc code chinh tu workspace, nen phan lon thay doi PHP se duoc cap nhat ngay ma khong can rebuild.

Ban van can rebuild neu thay doi:

- `Dockerfile`
- dependency PHP
- dependency Node
- bat ky thu gi duoc bake san vao image

## Cach chay test

Chay toan bo test:

```bash
docker compose exec app php artisan test
```

Chay rieng 1 file test:

```bash
docker compose exec app php artisan test tests/Feature/ManagementRoutePermissionTest.php
```

Chay theo ten class hoac ten test:

```bash
docker compose exec app php artisan test --filter=AuthRegisterValidationTest
```

Neu ban dang dung che do Aiven va van muon chay test trong container:

```bash
docker compose -f docker-compose.yml -f docker-compose.aiven.yml exec app php artisan test
```

## Cach them test case moi

Tao test feature moi:

```bash
docker compose exec app php artisan make:test UserManagementTest
```

Tao test unit moi:

```bash
docker compose exec app php artisan make:test UserHelperTest --unit
```

Sau khi tao:

- Test feature nam trong `tests/Feature`
- Test unit nam trong `tests/Unit`
- Them case moi vao file vua tao
- Chay lai `php artisan test` de kiem tra

Quy uoc de de doc:

- Test API, route, permission, auth: dat trong `tests/Feature`
- Test ham xu ly nho, helper, logic don le: dat trong `tests/Unit`

## Luu y

- `phpMyAdmin` luon tro toi container `db` local, khong tro toi Aiven.
- Frontend assets dang duoc build san trong image. Neu sua frontend lon, hay rebuild hoac build lai trong container.
- Schema MVP moi hien duoc quan ly bang `database/migrations`.
- Khi schema da on dinh va ban muon chot baseline moi, co the dung `php artisan schema:dump --prune`.
