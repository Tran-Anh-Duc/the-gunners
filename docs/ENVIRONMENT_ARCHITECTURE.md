# Tai lieu moi truong du an

## 1. Muc tieu

Tai lieu nay mo ta cach tach moi truong hien tai cua du an de ca team dev, tester va PM co cung cach hieu:

- moi truong local/dev-test dung chung du lieu tren Aiven;
- moi truong production chay app Laravel va MySQL tren Hetzner;
- du lieu dev/test va production tach biet hoan toan.

Tai lieu nay dung de tranh nham lan khi:

- cau hinh Docker;
- seed du lieu;
- kiem tra API;
- bao bug;
- deploy production.

## 2. So do tong quan

```text
+---------------------------+          +---------------------------+
| Local Dev / Local Tester  |          | Production               |
|                           |          |                           |
| Laravel app chay local    |          | Laravel app tren Hetzner |
| Docker / PHP local        |          | Nginx / PHP-FPM          |
|                           |          |                           |
| DB dung chung: Aiven      |          | DB production: MySQL     |
|                           |          | tren Hetzner             |
+-------------+-------------+          +-------------+-------------+
              |                                        |
              v                                        v
      +---------------+                       +------------------+
      | Aiven MySQL   |                       | Hetzner MySQL    |
      | Shared Dev DB |                       | Production DB    |
      +---------------+                       +------------------+
```

## 3. Nguyen tac quan trong

### 3.1. Aiven khong phai production

Aiven trong du an nay chi dong vai tro:

- DB dung chung cho dev va tester;
- noi de reproduce bug;
- noi de kiem tra du lieu chung;
- noi de PM/tester/dev cung nhin mot bo du lieu.

Khong dung Aiven lam DB production trong mo hinh hien tai.

### 3.2. Hetzner la production day du

Production tren Hetzner se gom:

- code Laravel;
- web server;
- PHP runtime;
- MySQL production.

Nghia la production la mot moi truong hoan chinh, khong phu thuoc vao Aiven.

### 3.3. Du lieu dev/test va production phai tach biet

Tuyet doi khong:

- seed du lieu demo vao production;
- migrate:fresh production de test;
- dung production DB de dev/test API thu cong.

## 4. Cac moi truong hien tai

### 4.1. Moi truong local dev/test

Muc dich:

- dev code hang ngay;
- tester/dev dung chung du lieu de bao bug;
- check API tren cung mot bo du lieu.

Cach chay:

- app Laravel chay local;
- DB tro toi Aiven;
- co the chay bang Docker mode Aiven.

Dac diem:

- de phoi hop team;
- du lieu dong bo cho nhieu nguoi;
- nhung se cham hon local DB vi app local goi toi remote DB.

### 4.2. Moi truong local compare

Muc dich:

- benchmark;
- test nhanh;
- so sanh voi Aiven khi can.

Cach chay:

- app local Docker;
- MySQL local Docker;
- du lieu seed rieng.

Moi truong nay dung cho ky thuat, khong phai moi truong team dung chung.

### 4.3. Moi truong production

Muc dich:

- moi truong chay that cho nguoi dung;
- deploy code tu GitHub;
- van hanh release.

Cau truc:

- app Laravel tren Hetzner;
- MySQL tren Hetzner;
- app va DB nam cung mot ha tang production.

Loi ich:

- latency tot hon local app -> remote DB;
- chu dong hon ve hieu nang;
- du lieu production tach biet khoi du lieu dev/test.

## 5. Khi nao dung moi truong nao

### Dung Aiven khi:

- tester can dung chung du lieu voi dev;
- can reproduce bug tren bo du lieu team dang dung;
- can PM/tester/dev cung check cung mot record;
- can chia se bug theo ID, du lieu, trang thai chung.

### Dung local compare khi:

- can kiem tra API co cham do code hay do remote DB;
- can test nhanh tren du lieu local;
- can benchmark endpoint.

### Dung production Hetzner khi:

- release tinh nang;
- smoke test sau deploy;
- xac nhan luong thuc te cho nguoi dung;
- kiem tra loi chi xuat hien trong moi truong production.

## 6. Rule cho team

### 6.1. Tren Aiven

Duoc phep:

- doc du lieu;
- test API;
- seed du lieu demo co kiem soat;
- reproduce bug.

Khong duoc phep:

- `migrate:fresh` neu chua thong bao ca team;
- xoa du lieu hang loat de test ca nhan;
- sua seed lam vo du lieu chung ma khong thong bao.

### 6.2. Tren production Hetzner

Duoc phep:

- deploy ban da duoc review;
- chay `php artisan migrate --force` khi release;
- backup truoc cac thay doi nhay cam.

Khong duoc phep:

- seed demo data vao production;
- dung tay test pha du lieu that;
- chay lenh reset DB.

## 7. Workflow de xuat

### 7.1. Workflow dev/test

1. Dev code o local.
2. Neu can test nhanh thi dung local compare.
3. Khi can share bug/du lieu voi tester thi tro ve Aiven.
4. Tester bao bug dua tren du lieu Aiven dung chung.
5. Dev reproduce bug tren cung du lieu Aiven.

### 7.2. Workflow deploy production

1. Dev push code len GitHub.
2. Server Hetzner pull code moi.
3. Chay lenh deploy Laravel.
4. Chay migrate production.
5. Smoke test sau deploy.

Lenh deploy co the gom:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Neu co frontend build:

```bash
npm install
npm run build
```

Neu co queue:

```bash
php artisan queue:restart
```

## 8. Van de hieu nang can nho

Local app ket noi Aiven se co do tre cao hon vi:

- app chay tren may dev;
- DB nam tren remote service;
- moi query di qua internet;
- request Laravel co the co nhieu query nho.

Vi vay:

- cham tren Aiven khong co nghia la logic nghiep vu sai;
- local compare dung de tach van de code va van de ha tang;
- production Hetzner co kha nang nhanh hon vi app va MySQL gan nhau hon.

## 9. Ket luan

Mo hinh hien tai duoc chot nhu sau:

- dev/test dung chung du lieu tren Aiven;
- production chay Laravel + MySQL tren Hetzner;
- local compare chi dung cho benchmark va debug hieu nang.

Day la mo hinh can bang giua:

- kha nang phoi hop team;
- chi phi ha tang;
- kha nang mo rong production ve sau.
