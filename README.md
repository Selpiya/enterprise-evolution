# Enterprise Evolution

## Integrasi Sistem dan Komputasi Awan

### Checkpoint 1 — Analisis Integrasi, API Contract, dan Running Service

Proyek **Enterprise Evolution** dikembangkan sebagai bagian dari mata kuliah **Integrasi Sistem dan Komputasi Awan** dengan studi kasus **Bintang Agency/Kranjang Community**.

Proyek ini merancang integrasi beberapa komponen sistem yang sebelumnya bekerja secara terpisah. Fokus utama pada Checkpoint 1 adalah merancang arsitektur integrasi, membuat kontrak API secara design-first, mengimplementasikan REST API, serta membangun komunikasi asynchronous menggunakan RabbitMQ.

---

# 1. Anggota Kelompok

| NIM     | Nama                        |
| ------- | --------------------------- |
| 3224007 | Selpiya                     |
| 3224008 | Nayara Zakhira Kurnia Putri |
| 3224014 | Muhammad Mukhlis Rifdulloh  |
| 3224019 | Risma Marisa                |

---

# 2. Studi Kasus

Studi kasus yang digunakan adalah **Bintang Agency/Kranjang Community**.

Berdasarkan analisis awal, terdapat beberapa komponen yang digunakan dalam proses pengelolaan creator, campaign, transaksi affiliate, dan monitoring performa.

Komponen tersebut kemudian direpresentasikan menjadi beberapa service agar batas tanggung jawab setiap bagian lebih jelas dan integrasi antarsistem dapat dirancang secara terstruktur.

---

# 3. Arsitektur Sistem

Arsitektur yang dirancang terdiri dari empat service utama:

1. **Creator Data Service**
2. **Campaign Service**
3. **Affiliate Service**
4. **Monitoring Service**

Diagram sederhana:

```text
+----------------------+
| Creator Data Service |
+----------+-----------+
           |
           | REST / HTTP
           v
+----------------------+
|   Campaign Service   |
+----------------------+


+----------------------+
|  Affiliate Service   |
+----------+-----------+
           |
           | Event:
           | TransaksiAffiliateTercatat
           v
+----------------------+
|      RabbitMQ        |
|                      |
| Exchange:            |
| affiliate.events     |
+----------+-----------+
           |
           | Routing Key:
           | affiliate.transaksi.tercatat
           v
+------------------------------+
| monitoring.transaksi-affiliate|
+---------------+--------------+
                |
                v
+----------------------+
| Monitoring Service   |
+----------------------+
```

---

# 4. Batas Tanggung Jawab Service

## 4.1 Creator Data Service

Creator Data Service merepresentasikan sumber data creator dan insight yang pada kondisi awal berkaitan dengan penggunaan Kalodata.

Service ini bertanggung jawab menyediakan data creator yang diperlukan pada proses pengelolaan campaign.

Pada Checkpoint 1, Creator Data Service masih berada pada tingkat desain arsitektur dan belum diimplementasikan sebagai service Laravel yang berjalan.

---

## 4.2 Campaign Service

Campaign Service merepresentasikan proses pengelolaan campaign dan creator yang sebelumnya berkaitan dengan penggunaan Google Spreadsheet.

Campaign Service dirancang untuk menerima data creator melalui komunikasi synchronous menggunakan REST API.

Pada Checkpoint 1, service ini masih berada pada tingkat desain arsitektur.

---

## 4.3 Affiliate Service

Affiliate Service merepresentasikan bagian sistem yang mencatat transaksi affiliate.

Service ini telah diimplementasikan menggunakan **Laravel** dan **Laravel Sail**.

Tanggung jawab utamanya adalah:

* menerima data transaksi affiliate melalui REST API;
* melakukan validasi request;
* menyimpan transaksi ke database;
* menghasilkan `transaction_id`;
* menerbitkan event `TransaksiAffiliateTercatat`;
* mengirimkan event ke RabbitMQ.

---

## 4.4 Monitoring Service

Monitoring Service merepresentasikan sistem monitoring internal.

Service ini telah diimplementasikan menggunakan **Laravel** dan **Laravel Sail**.

Tanggung jawab utamanya adalah:

* menjalankan consumer RabbitMQ;
* menerima event transaksi affiliate;
* membaca payload event;
* menyimpan transaksi ke database monitoring;
* mencegah data duplikat berdasarkan `transaction_id`.

---

# 5. Pola Integrasi

Proyek menggunakan dua pola komunikasi.

## 5.1 Synchronous Integration

Jalur synchronous dirancang antara:

```text
Creator Data Service
        |
        | REST / HTTP
        v
Campaign Service
```

REST dipilih karena Campaign Service membutuhkan respons langsung ketika meminta atau menerima data creator.

---

## 5.2 Asynchronous Integration

Jalur asynchronous digunakan antara:

```text
Affiliate Service
        |
        | Publish Event
        v
RabbitMQ
        |
        | Consume Event
        v
Monitoring Service
```

Pendekatan asynchronous digunakan agar Affiliate Service tidak perlu melakukan pemanggilan langsung kepada Monitoring Service setiap kali transaksi tercatat.

RabbitMQ berfungsi sebagai message broker yang memisahkan publisher dan consumer.

---

# 6. Struktur Repository

```text
enterprise-evolution/
|
├── affiliate-service/
|   ├── app/
|   ├── bootstrap/
|   ├── config/
|   ├── database/
|   ├── routes/
|   ├── tests/
|   └── ...
|
├── monitoring-service/
|   ├── app/
|   ├── database/
|   ├── tests/
|   └── ...
|
├── docs/
|   ├── openapi.yaml
|   └── event-contract.md
|
├── .spectral.yaml
├── package.json
└── README.md
```

---

# 7. API Contract

Kontrak REST API dibuat dengan pendekatan **design-first** menggunakan spesifikasi **OpenAPI 3.1.0**. File kontrak tersedia di:

```text
docs/openapi.yaml
```

Kontrak divalidasi menggunakan **Spectral** dengan konfigurasi ruleset pada file `.spectral.yaml`.

Jalankan perintah berikut dari folder utama repository `enterprise-evolution`:

```bash
npm ci
./node_modules/.bin/spectral lint docs/openapi.yaml --ruleset .spectral.yaml
```

Perintah `npm ci` memasang dependency sesuai dengan `package-lock.json`, sedangkan perintah berikutnya memeriksa kontrak OpenAPI menggunakan Spectral.

Target validasi: **0 error**.

---

# 8. Endpoint Affiliate Service

Base URL lokal:

```text
http://localhost
```

## 8.1 Membuat Transaksi Affiliate

```http
POST /api/v1/transaksi-affiliate
```

Contoh request:

```json
{
  "creator_id": "CRT-004",
  "campaign_id": "CMP-004",
  "nominal_transaksi": 500000,
  "nmv": 500000
}
```

Contoh menggunakan curl:

```bash
curl -X POST http://localhost/api/v1/transaksi-affiliate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "creator_id": "CRT-004",
    "campaign_id": "CMP-004",
    "nominal_transaksi": 500000,
    "nmv": 500000
  }'
```

Response sukses menggunakan status:

```text
201 Created
```

---

## 8.2 Mengambil Transaksi Affiliate

```http
GET /api/v1/transaksi-affiliate/{transaction_id}
```

Contoh:

```bash
curl -H "Accept: application/json" \
http://localhost/api/v1/transaksi-affiliate/TRX-XXXXXXXX
```

Jika transaksi ditemukan, API mengembalikan:

```text
200 OK
```

Jika transaksi tidak ditemukan, API mengembalikan:

```text
404 Not Found
Content-Type: application/problem+json
```

---

# 9. Problem Details

API menggunakan format **Problem Details** untuk response error.

Contoh error validasi:

```json
{
  "type": "https://example.com/problems/validation-error",
  "title": "Validation Error",
  "status": 422,
  "detail": "Data transaksi yang dikirim tidak valid.",
  "errors": {
    "creator_id": [
      "The creator id field is required."
    ]
  }
}
```

Content-Type:

```text
application/problem+json
```

API juga menggunakan Problem Details untuk kondisi transaksi tidak ditemukan dengan HTTP status `404`.

---

# 10. Event Contract

Kontrak event tersedia pada:

```text
docs/event-contract.md
```

Event utama:

```text
TransaksiAffiliateTercatat
```

Konfigurasi RabbitMQ:

| Komponen      | Nilai                            |
| ------------- | -------------------------------- |
| Publisher     | Affiliate Service                |
| Consumer      | Monitoring Service               |
| Broker        | RabbitMQ                         |
| Exchange      | `affiliate.events`               |
| Exchange Type | `topic`                          |
| Routing Key   | `affiliate.transaksi.tercatat`   |
| Queue         | `monitoring.transaksi-affiliate` |

---

# 11. Payload Event

Contoh pesan yang diterbitkan:

```json
{
  "transaction_id": "TRX-SM93T2HT",
  "creator_id": "CRT-004",
  "campaign_id": "CMP-004",
  "nominal_transaksi": 500000,
  "nmv": 500000,
  "occurred_at": "2026-10-23T09:28:35+00:00"
}
```

---

# 12. Requirement

Lingkungan pengembangan yang digunakan:

* WSL/Linux
* Docker Desktop
* PHP
* Composer
* Node.js
* npm
* Laravel
* Laravel Sail
* MySQL
* RabbitMQ
* php-amqplib/php-amqplib
* Spectral CLI

Pastikan Docker Desktop aktif sebelum menjalankan Laravel Sail.

---

# 13. Menjalankan Affiliate Service dari Nol

Masuk ke folder service:

```bash
cd ~/enterprise-evolution/affiliate-service
```

Install dependency:

```bash
composer install
```

Buat file environment jika belum tersedia:

```bash
cp .env.example .env
```

Pastikan konfigurasi database menggunakan:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

Konfigurasi RabbitMQ:

```env
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=affiliate
RABBITMQ_PASSWORD=pw123
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=monitoring.transaksi-affiliate
```

Jalankan container:

```bash
./vendor/bin/sail up -d
```
Periksa status container:

```bash
./vendor/bin/sail ps
```

Pastikan container aplikasi berstatus berjalan, serta MySQL dan RabbitMQ sudah berstatus `healthy`.

Pada startup pertama, proses inisialisasi dapat membutuhkan waktu. Jika status masih `starting`, tunggu lalu jalankan kembali pemeriksaan status sebelum melanjutkan migration.

Generate application key jika diperlukan:

```bash
./vendor/bin/sail artisan key:generate
```

Jalankan migration:

```bash
./vendor/bin/sail artisan migrate
```

Affiliate Service kemudian dapat diakses melalui:

```text
http://localhost
```

---

# 14. Menjalankan Monitoring Service dari Nol

Masuk ke folder:

```bash
cd ~/enterprise-evolution/monitoring-service
```

Install dependency:

```bash
composer install
```

Buat environment:

```bash
cp .env.example .env
```

Konfigurasi port:

```env
APP_PORT=8081
FORWARD_DB_PORT=3307
VITE_PORT=5174
```

Konfigurasi RabbitMQ:

```env
RABBITMQ_HOST=host.docker.internal
RABBITMQ_PORT=5672
RABBITMQ_USER=affiliate
RABBITMQ_PASSWORD=pw123
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=monitoring.transaksi-affiliate
```

Jalankan service:

```bash
./vendor/bin/sail up -d
```
Periksa status container:

```bash
./vendor/bin/sail ps
```

Pastikan container aplikasi berstatus berjalan dan MySQL sudah berstatus `healthy` sebelum melanjutkan migration.

Generate key jika diperlukan:

```bash
./vendor/bin/sail artisan key:generate
```

Jalankan migration:

```bash
./vendor/bin/sail artisan migrate
```

---

# 15. Menjalankan RabbitMQ Consumer

Setelah Affiliate Service dan Monitoring Service aktif, buka terminal untuk Monitoring Service:

```bash
cd ~/enterprise-evolution/monitoring-service
```

Jalankan:

```bash
./vendor/bin/sail artisan monitoring:konsumsi-transaksi-affiliate
```

Consumer akan menunggu pesan dari queue:

```text
monitoring.transaksi-affiliate
```

Ketika Affiliate Service menerbitkan event, Monitoring Service akan menerima pesan dan menyimpannya ke database.

---

# 16. Port Layanan dan Pemeriksaan RabbitMQ

## 16.1 Port Layanan

Konfigurasi default menggunakan port host berikut:

| Komponen | Port host |
| --- | --- |
| Affiliate Service HTTP | 80 |
| Monitoring Service HTTP | 8081 |
| MySQL Affiliate | 3306 |
| MySQL Monitoring | 3307 |
| RabbitMQ AMQP | 5672 |
| RabbitMQ Management UI | 15672 |

Pastikan port tersebut tersedia sebelum menjalankan layanan.

Port database di dalam container tetap `3306`. Nilai `3307` merupakan port host untuk mengakses MySQL Monitoring, sehingga konfigurasi `DB_PORT` di dalam aplikasi Monitoring tetap `3306`.

## 16.2 Membuka RabbitMQ Management UI

Buka:

```text
http://localhost:15672
```

Kredensial development sesuai `.env.example`:

```text
Username: affiliate
Password: pw123
```

## 16.3 Memeriksa Exchange, Binding, Queue, dan Consumer

Komponen yang digunakan:

| Komponen | Nilai |
| --- | --- |
| Exchange | affiliate.events |
| Exchange type | topic |
| Routing key | affiliate.transaksi.tercatat |
| Queue | monitoring.transaksi-affiliate |
| Publisher | Affiliate Service |
| Consumer | Monitoring Service |

Lakukan pemeriksaan berikut:

1. Jalankan consumer Monitoring sesuai bagian 15.
2. Kirim transaksi valid melalui API sesuai bagian 20.
3. Buka menu Exchanges dan pilih `affiliate.events`.
4. Periksa binding menuju queue `monitoring.transaksi-affiliate` dengan routing key `affiliate.transaksi.tercatat`.
5. Buka menu Queues and Streams, kemudian pilih queue tersebut.
6. Periksa consumer yang terhubung serta nilai Ready dan Unacked.

Publisher mendeklarasikan exchange, queue, dan binding saat menerbitkan event. Karena itu, lakukan pengiriman transaksi valid sebelum memeriksa kelengkapan exchange dan binding.

Nilai Ready dan Unacked dapat kembali menjadi nol setelah pesan selesai diproses dan diakui oleh consumer. Cocokkan pemeriksaan broker dengan log consumer dan data pada database Monitoring.

## 16.4 Koneksi RabbitMQ Antarlayanan

Affiliate Service menggunakan:

```env
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
```

Monitoring Service menggunakan:

```env
RABBITMQ_HOST=host.docker.internal
RABBITMQ_PORT=5672
```

Affiliate mengakses RabbitMQ melalui jaringan Compose miliknya. Monitoring menggunakan port RabbitMQ yang dipublikasikan pada host.

---

# 17. Menjalankan Feature Test Affiliate Service

Masuk ke:

```bash
cd ~/enterprise-evolution/affiliate-service
```

Jalankan seluruh test:

```bash
./vendor/bin/sail artisan test
```

Feature test mencakup:

* POST transaksi valid;
* GET transaksi;
* response 404 menggunakan Problem Details;
* response 422 untuk validation error.

---

# 18. Menjalankan Feature Test Monitoring Service

Masuk ke:

```bash
cd ~/enterprise-evolution/monitoring-service
```

Jalankan:

```bash
./vendor/bin/sail artisan test
```

Test Monitoring Service mencakup:

* penyimpanan transaksi yang berasal dari event;
* idempotensi berdasarkan `transaction_id`;
* event yang sama tidak menghasilkan baris transaksi duplikat.

---

# 19. Idempotensi Consumer

Monitoring Service menggunakan:

```text
transaction_id
```

sebagai identitas unik transaksi.

Jika pesan yang sama diterima kembali, service menggunakan mekanisme:

```php
updateOrCreate()
```

sehingga data dengan `transaction_id` yang sama tidak menghasilkan baris baru.

---

# 20. Alur Demo

Urutan demo teknis dapat dilakukan sebagai berikut.

### 1. Jalankan Affiliate Service

```bash
cd ~/enterprise-evolution/affiliate-service
./vendor/bin/sail up -d
```

### 2. Jalankan Monitoring Service

```bash
cd ~/enterprise-evolution/monitoring-service
./vendor/bin/sail up -d
```

### 3. Jalankan Consumer

```bash
./vendor/bin/sail artisan monitoring:konsumsi-transaksi-affiliate
```

### 4. Kirim POST ke Affiliate Service

```bash
curl -X POST http://localhost/api/v1/transaksi-affiliate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "creator_id": "CRT-DEMO",
    "campaign_id": "CMP-DEMO",
    "nominal_transaksi": 500000,
    "nmv": 500000
  }'
```

### 5. Perhatikan Terminal Consumer

Monitoring Service akan menampilkan bahwa transaksi telah diterima dan disimpan.

### 6. Periksa RabbitMQ

Buka:

```text
http://localhost:15672
```

Periksa exchange, routing key, queue, dan consumer.

### 7. Jalankan Test

Affiliate Service:

```bash
./vendor/bin/sail artisan test
```

Monitoring Service:

```bash
./vendor/bin/sail artisan test
```

---

# 21. Pembagian Tugas

Pembagian tugas kelompok pada Checkpoint 1:

| Anggota                     | Tanggung Jawab                                                                       |
| --------------------------- | ------------------------------------------------------------------------------------ |
| Selpiya                     | Analisis kebutuhan integrasi, pengembangan Affiliate Service, dan pengujian REST API |
| Nayara Zakhira Kurnia Putri | Penyusunan kontrak OpenAPI, dokumentasi API, dan validasi Spectral                   |
| Muhammad Mukhlis Rifdulloh  | Implementasi RabbitMQ, publisher, exchange, queue, dan event contract                |
| Risma Marisa                | Monitoring Service, consumer RabbitMQ, pengujian idempotensi, dan dokumentasi proyek |


---

# 22. Status Implementasi Checkpoint 1

| Komponen                | Status      |
| ----------------------- | ----------- |
| Analisis arsitektur     | Selesai     |
| Creator Data Service    | Design      |
| Campaign Service        | Design      |
| Affiliate Service       | Implemented |
| Monitoring Service      | Implemented |
| OpenAPI Contract        | Selesai     |
| Spectral Validation     | 0 Error     |
| REST API                | Berjalan    |
| Problem Details         | Berjalan    |
| RabbitMQ Publisher      | Berjalan    |
| RabbitMQ Consumer       | Berjalan    |
| Event Contract          | Selesai     |
| Idempotensi Consumer    | Berjalan    |
| Feature Test Affiliate  | PASS        |
| Feature Test Monitoring | PASS        |

---

# 23. Kesimpulan

Enterprise Evolution menerapkan dua pola integrasi, yaitu synchronous REST dan asynchronous messaging.

REST digunakan pada rancangan komunikasi antara Creator Data Service dan Campaign Service, sedangkan RabbitMQ digunakan untuk komunikasi asynchronous antara Affiliate Service dan Monitoring Service.

Pada Checkpoint 1, implementasi utama difokuskan pada Affiliate Service dan Monitoring Service. Affiliate Service telah dapat menerima transaksi melalui REST API dan menerbitkan event `TransaksiAffiliateTercatat`, sedangkan Monitoring Service telah dapat menerima event tersebut melalui RabbitMQ dan menyimpannya ke database secara idempotent.

Dokumentasi API disediakan melalui `openapi.yaml`, sedangkan kontrak komunikasi asynchronous didokumentasikan melalui `event-contract.md`.
