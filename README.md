# 👶 Giza Kids - Product Management System

A simple web-based product management system for a children's clothing store.

## 🚀 Deploy on Vercel

1. Push this code to a GitHub repo

2. On Vercel, import the repo
   - **Framework**: Other
   - **Root Directory**: `childrens-shop`
   - **Build Command**: (leave empty)
   - **Output Directory**: (leave empty)

3. Add Environment Variables:
   - `ADMIN_PASSWORD` = Your password (default: `store2026`)
   - `DB_HOST` = Your MySQL host (e.g., from PlanetScale, Aiven)
   - `DB_USER` = Your MySQL user
   - `DB_PASS` = Your MySQL password
   - `DB_NAME` = Your database name

4. Run `setup.sql` on your MySQL database first

5. Deploy!

## 🔧 Local Setup

1. Create MySQL database and run `setup.sql`
2. Copy `config/config.php` and set your DB credentials
3. Set `ADMIN_PASSWORD` in `config/config.php`
4. Run: `php -S localhost:8000`
5. Visit: `http://localhost:8000/admin/index.php`

## 📱 Features

- ✅ Add/Edit/Delete products
- ✅ QR code generation (in-browser)
- ✅ QR scanner (mobile camera)
- ✅ Bulk delete & QR download
- ✅ Search & sort products
- ✅ 4 price levels (purchase, sale, min, max)
- ✅ Mobile-friendly

## 🔐 Default Login

Password: `store2026` (change via config or env var)

## 📦 Tech

- PHP (Vercel Serverless)
- MySQL
- QRCode.js
- html5-qrcode
- JSZip
