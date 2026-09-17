# Borang Bayar Zakat — Rakanzakat.com

Design a clean Malaysian zakat payment form for desktop and mobile. White background, lots of breathing room, rounded light-grey inputs, yellow primary button bottom-right. Malay language. Looks like a trusted zakat institution form, not a startup landing page.

## Page

- Title is not needed in the form card itself; this is a form section only.
- Single column card, max width ~880px, centered.
- Two-column fields on desktop, one column on mobile.

## Fields (in this exact order)

### Row 1 — two columns
1. **Nama Penuh / Nama Syarikat@Organisasi** *  
   Helper icon (i). Placeholder: `Nama penuh pembayar zakat | Syarikat seperti SSM`
2. **No. Telefon** *  
   Malaysia flag prefix. Placeholder: `Contoh: 0129876543`

### Row 2 — full width
3. **Jenis Pengenalan** *  
   Dropdown placeholder: `- Sila Pilih -`  
   Options: MyKad / NRIC, Passport, SSM (Syarikat), No. Polis, No. Tentera, Lain-lain

### Alamat Penuh (section heading)
4. **Alamat Baris 1** * — placeholder `Alamat Baris 1`
5. **Alamat Baris 2** — placeholder `Alamat Baris 2` (same row as baris 1 on desktop)
6. **Bandar** * — placeholder `Bandar`
7. **Negeri** * — dropdown, default selected `Selangor`  
   Options: Johor, Kedah, Kelantan, Melaka, Negeri Sembilan, Pahang, Perak, Perlis, Pulau Pinang, Sabah, Sarawak, Selangor, Terengganu, WP Kuala Lumpur, WP Labuan, WP Putrajaya
8. **Poskod** * — placeholder `Poskod`, half width, 5 digits

### Row — full width
9. **Emel** * — placeholder `ali@email.com`
10. **Jenis Zakat** *  
    Helper icon (i). Dropdown placeholder: `- Sila Pilih Jenis Zakat -`  
    Options: Zakat Pendapatan, Zakat Fitrah, Zakat Perniagaan, Zakat Simpanan, Zakat Emas, Zakat Saham, Zakat KWSP, Zakat Pertanian, Lain-lain / Sumbangan
11. **Haul/Tahun** *  
    Dropdown placeholder: `- Tahun -`  
    Options: 2026, 2025, 2024, 2023, 2022, 2021  
    Half width
12. **Amaun (RM)** *  
    Number input. Show quick chips: RM50, RM100, RM250, RM500, RM1000. Amount updates the niat text below.

### Niat
13. Checkbox **Niat Membayar Zakat** (required)
14. Body text under checkbox:  
    `Inilah wang sebanyak RM0.00 sebagai menunaikan zakat yang wajib ke atas diri saya kerana ALLAH Ta'ala.`  
    `RM0.00` is bold and live-updates from Amaun (example: **RM150.00**).

### CTA
15. Button **Bayar Sekarang** — pill shape, bright yellow `#F5D000`, black/dark text, bottom right of the form. Do not submit until niat is checked and required fields filled.

## Validation
- Required fields marked with red asterisk.
- Disabled/empty dropdowns show the “Sila Pilih” placeholder.
- Phone digits only, Malaysian mobile.
- Poskod exactly 5 digits.
- Button aligned right, not full width.

## Visual style
- Font: clean sans (system).
- Labels: dark grey, regular weight, slightly small.
- Inputs: white, 12px radius, 1px #E5E7EB border, 14px padding.
- Section “Alamat Penuh” as a label group, not a heavy box.
- No illustrations, no hero image, no extra marketing copy.
