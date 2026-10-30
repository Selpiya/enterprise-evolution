# Event Contract — TransaksiAffiliateTercatat

## 1. Nama Event

`TransaksiAffiliateTercatat`

Event ini merepresentasikan fakta bahwa sebuah transaksi affiliate telah berhasil dicatat oleh **Affiliate Service**.

Nama event menggunakan bentuk lampau karena event menunjukkan suatu kejadian yang sudah terjadi di dalam sistem.

---

## 2. Publisher

`Affiliate Service`

Affiliate Service bertanggung jawab untuk:

* menerima dan mencatat transaksi affiliate;
* menyimpan transaksi ke database;
* membentuk event `TransaksiAffiliateTercatat`;
* menerbitkan event tersebut ke RabbitMQ setelah transaksi berhasil dicatat.

---

## 3. Consumer

`Monitoring Service`

Monitoring Service bertanggung jawab untuk:

* menerima event `TransaksiAffiliateTercatat` dari RabbitMQ;
* membaca payload transaksi affiliate;
* menyimpan data transaksi ke database monitoring;
* mencegah terjadinya duplikasi data berdasarkan `transaction_id`.

---

## 4. Message Broker

Message broker yang digunakan adalah:

`RabbitMQ`

RabbitMQ digunakan sebagai perantara komunikasi asynchronous antara Affiliate Service dan Monitoring Service.

Dengan mekanisme ini, Affiliate Service tidak perlu melakukan pemanggilan langsung ke Monitoring Service ketika transaksi affiliate dicatat.

---

## 5. Exchange

Nama exchange:

`affiliate.events`

Tipe exchange:

`topic`

Exchange `affiliate.events` digunakan untuk menerima event yang diterbitkan oleh Affiliate Service dan meneruskannya menuju queue yang sesuai berdasarkan routing key.

Exchange dikonfigurasi sebagai **durable**, sehingga tetap tersedia ketika RabbitMQ melakukan restart selama data volume tetap dipertahankan.

---

## 6. Routing Key

Routing key yang digunakan:

`affiliate.transaksi.tercatat`

Routing key ini digunakan untuk menandai event yang menunjukkan bahwa sebuah transaksi affiliate telah berhasil dicatat.

---

## 7. Queue

Nama queue:

`monitoring.transaksi-affiliate`

Queue ini digunakan oleh Monitoring Service untuk menerima event `TransaksiAffiliateTercatat`.

Queue dikonfigurasi sebagai **durable** sehingga dapat tetap tersedia ketika RabbitMQ melakukan restart.

---

## 8. Payload Event

Contoh payload event yang dikirim oleh Affiliate Service:

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

## 9. Struktur Field Event

| Field               | Tipe Data          | Wajib | Keterangan                                      |
| ------------------- | ------------------ | ----- | ----------------------------------------------- |
| `transaction_id`    | string             | Ya    | ID unik untuk setiap transaksi affiliate        |
| `creator_id`        | string             | Ya    | ID creator yang terkait dengan transaksi        |
| `campaign_id`       | string             | Ya    | ID campaign yang terkait dengan transaksi       |
| `nominal_transaksi` | integer            | Ya    | Nilai nominal transaksi affiliate               |
| `nmv`               | integer            | Ya    | Nilai NMV yang dicatat dari transaksi affiliate |
| `occurred_at`       | string (date-time) | Ya    | Waktu ketika transaksi affiliate dicatat        |

---

## 10. Alur Event

Alur komunikasi asynchronous yang digunakan adalah sebagai berikut:

```text
Affiliate Service
        ↓
Transaksi affiliate dicatat
        ↓
Event: TransaksiAffiliateTercatat
        ↓
Exchange: affiliate.events
        ↓
Routing Key: affiliate.transaksi.tercatat
        ↓
Queue: monitoring.transaksi-affiliate
        ↓
Monitoring Service
        ↓
Data transaksi disimpan
ke database monitoring
```

Proses dimulai ketika transaksi affiliate berhasil dicatat pada Affiliate Service. Setelah transaksi tersimpan, sistem menghasilkan event `TransaksiAffiliateTercatat`.

Event kemudian diterbitkan ke exchange `affiliate.events` menggunakan routing key `affiliate.transaksi.tercatat`. RabbitMQ meneruskan pesan tersebut ke queue `monitoring.transaksi-affiliate`.

Monitoring Service menjalankan consumer untuk membaca pesan dari queue tersebut. Setelah payload diterima, data transaksi kemudian disimpan ke database Monitoring Service.

---

## 11. Message Properties

Pesan yang diterbitkan ke RabbitMQ menggunakan beberapa properti berikut:

| Properti      | Nilai                            |
| ------------- | -------------------------------- |
| Content Type  | `application/json`               |
| Delivery Mode | Persistent                       |
| Event Type    | `TransaksiAffiliateTercatat`     |
| Exchange      | `affiliate.events`               |
| Exchange Type | `topic`                          |
| Routing Key   | `affiliate.transaksi.tercatat`   |
| Queue         | `monitoring.transaksi-affiliate` |

Penggunaan **persistent delivery mode** bertujuan agar pesan dapat dipertahankan oleh RabbitMQ ketika menggunakan queue dan exchange yang durable.

---

## 12. Idempotensi

Monitoring Service menggunakan field:

`transaction_id`

sebagai identitas unik transaksi.

Ketika event dengan `transaction_id` yang sama diterima lebih dari satu kali, Monitoring Service tidak membuat data transaksi baru.

Implementasi penyimpanan menggunakan mekanisme yang mencari transaksi berdasarkan `transaction_id` terlebih dahulu. Jika transaksi sudah ada, data diperbarui. Jika belum ada, data baru dibuat.

Dengan mekanisme ini, pengiriman ulang event tidak menyebabkan duplikasi data pada database Monitoring Service.

Contoh:

```text
Event pertama:
transaction_id = TRX-TEST001
        ↓
Data dibuat

Event kedua:
transaction_id = TRX-TEST001
        ↓
Data yang sama ditemukan
        ↓
Tidak membuat baris duplikat
```

---

## 13. Publisher–Consumer Relationship

Hubungan antar komponen dapat diringkas sebagai berikut:

| Komponen                         | Peran          |
| -------------------------------- | -------------- |
| Affiliate Service                | Publisher      |
| RabbitMQ                         | Message Broker |
| `affiliate.events`               | Exchange       |
| `affiliate.transaksi.tercatat`   | Routing Key    |
| `monitoring.transaksi-affiliate` | Queue          |
| Monitoring Service               | Consumer       |

Affiliate Service dan Monitoring Service tidak melakukan komunikasi langsung pada jalur ini. Pertukaran data dilakukan melalui RabbitMQ sehingga kedua layanan memiliki coupling yang lebih rendah.

---

## 14. Penanganan Pesan

Consumer Monitoring Service membaca pesan dari queue `monitoring.transaksi-affiliate`.

Setelah payload berhasil diproses dan disimpan, consumer memberikan acknowledgement terhadap pesan tersebut.

Alur sederhananya:

```text
Pesan diterima
      ↓
Payload dibaca
      ↓
Data disimpan
      ↓
Acknowledgement
```

Acknowledgement digunakan untuk menandai bahwa pesan telah berhasil diproses oleh consumer.

---

## 15. Contoh Skenario

### Skenario Normal

1. Affiliate Service menerima transaksi affiliate.
2. Transaksi berhasil disimpan.
3. Event `TransaksiAffiliateTercatat` dibuat.
4. Event diterbitkan ke exchange `affiliate.events`.
5. RabbitMQ meneruskan event ke queue `monitoring.transaksi-affiliate`.
6. Monitoring Service menerima event.
7. Data transaksi disimpan ke database monitoring.
8. Consumer memberikan acknowledgement.

### Skenario Event Diterima Kembali

1. Event dengan `transaction_id` yang sama diterima kembali.
2. Monitoring Service melakukan pengecekan berdasarkan `transaction_id`.
3. Data yang sudah ada digunakan atau diperbarui.
4. Baris transaksi baru tidak dibuat.
5. Database tetap memiliki satu data untuk transaksi tersebut.

---

## 16. Ringkasan Kontrak Event

| Elemen                | Nilai                            |
| --------------------- | -------------------------------- |
| Nama Event            | `TransaksiAffiliateTercatat`     |
| Publisher             | `Affiliate Service`              |
| Consumer              | `Monitoring Service`             |
| Message Broker        | RabbitMQ                         |
| Exchange              | `affiliate.events`               |
| Exchange Type         | `topic`                          |
| Routing Key           | `affiliate.transaksi.tercatat`   |
| Queue                 | `monitoring.transaksi-affiliate` |
| Content Type          | `application/json`               |
| Delivery Mode         | Persistent                       |
| Identitas Idempotensi | `transaction_id`                 |

Kontrak event ini digunakan sebagai acuan komunikasi asynchronous antara Affiliate Service dan Monitoring Service dalam proyek **Enterprise Evolution** dengan studi kasus **Bintang Agency/Kranjang Community**.
