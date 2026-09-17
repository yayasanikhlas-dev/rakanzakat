# Rakan Zakat — plugin WordPress

Plugin dashboard untuk [rakanzakat.com](https://rakanzakat.com): kutipan zakat melalui **Billplz**, tracking pelawat, ads spend, dan ROI.

## Apa yang ada

- Borang bayar zakat `[rakanzakat_form]` (FPX / kad / e-wallet via Billplz)
- Dashboard admin: kutipan, pelawat, conversion, ROAS, ROI, CPA
- Callback + redirect Billplz dengan pengesahan X-Signature
- Tracking pelawat + UTM (last-touch attribution ke kempen)
- Rekod ads spend mengikut kempen / platform
- Export CSV kutipan
- Auto-update melalui GitHub Releases atau JSON HTTPS (macam plugin WordPress rasmi)

## Pasang

1. Zip folder `rakanzakat` (fail `rakanzakat.php` mesti dalam root zip).
2. WordPress → Plugins → Add New → Upload Plugin.
3. Aktifkan **Rakan Zakat**.
4. Plugin akan cipta halaman **Bayar Zakat** dan **Terima Kasih** automatik.

Atau salin folder ni ke `wp-content/plugins/rakanzakat`.

## Setup Billplz

1. Log masuk [Billplz](https://www.billplz.com) → Settings.
2. Salin **API Secret Key** dan **X Signature Key**.
3. Tick **Enable X Signature Payment Completion**.
4. Collection → salin **Collection ID**.
5. WordPress → **Rakan Zakat → Tetapan** → tampal kunci → **Uji sambungan**.

Guna sandbox dulu (`Guna Billplz sandbox`) dengan akaun [billplz-sandbox](https://www.billplz-sandbox.com).

Callback URL dihantar automatik bila bill dicipta:

`https://rakanzakat.com/wp-json/rakanzakat/v1/billplz/callback`

## Tracking iklan

Guna UTM pada URL landing:

```
https://rakanzakat.com/bayar-zakat/?utm_source=fb&utm_medium=cpc&utm_campaign=ramadan
```

Kemudian:

1. **Iklan & ROI** → tambah kempen dengan `utm_campaign` yang sama.
2. Rekod spend harian/mingguan dari Ads Manager.
3. Dashboard kira ROAS = kutipan / spend, ROI = (kutipan − spend) / spend.

## Shortcode, widget & block

| Cara | Fungsi |
|---|---|
| Landing <strong>Rakan Zakat — Landing</strong> | Page template plugin (`/zakat/`) |
| Elementor **Borang Zakat** | Drag borang bayar Billplz |
| Elementor **RZ: Panduan / Kategori / Tiga Langkah / Saluran Rasmi / Impak / FAQ / CTA** | Section Stitch, semua teks/gambar boleh edit |
| `[rakanzakat_form]` | Borang pembayaran (page, Elementor Shortcode, dll.) |
| Block **Borang Zakat** | Drag dalam page/post editor |
| Widget **Rakan Zakat — Borang Bayar** | Sidebar, footer, widget area |
| `[rakanzakat_receipt]` | Resit selepas Billplz redirect |

## Auto-update

Push ke `main` je. GitHub Action akan buat Release `v{version}` + `rakanzakat.zip` kalau nombor versi dalam `rakanzakat.php` belum ada release.

Aliran kerja:

1. Ubah kod.
2. Naikkan `Version` dan `RAKANZAKAT_VERSION` dalam `rakanzakat.php` (contoh `1.6.1`).
3. `git add -A && git commit -m "..." && git push origin main`
4. WordPress site (Rakan Zakat → Tetapan → GitHub repo) akan nampak update. Tick auto-update.

Kalau versi tak berubah, push tak cipta release baru.

## Keperluan

- WordPress 6.0+
- PHP 7.4+
- REST API enabled (permalink bukan Plain lebih elok)
