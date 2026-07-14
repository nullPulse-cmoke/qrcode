# 👶 Giza Kids - Product Management System

A simple web-based product management system for a children's clothing store. Built for **Vercel + Supabase**.

## 🚀 Deploy

### 1. Supabase Setup
```bash
supabase login
supabase init
supabase link --project-ref YOUR_PROJECT_ID
```
Then run `setup.sql` in Supabase SQL Editor.

Your connection string will be:
```
postgresql://postgres:PASSWORD@db.YOUR_PROJECT.supabase.co:5432/postgres
```

### 2. Vercel Deploy
Push to GitHub → Import to Vercel → Add env vars:

| Variable | Value |
|----------|-------|
| `DATABASE_URL` | `postgresql://postgres:PASSWORD@db.YOUR_PROJECT.supabase.co:5432/postgres` |
| `ADMIN_PASSWORD` | `your_password` |

## 📱 Features
- ✅ Add/Edit/Delete products (4 price levels)
- ✅ QR code generation (in-browser with QRCode.js)
- ✅ QR scanner (mobile camera with html5-qrcode)
- ✅ Bulk delete & QR ZIP download
- ✅ Search & sort products
- ✅ Mobile-friendly

## 🔐 Default Login
Password: `store2026` (change via `ADMIN_PASSWORD` env var)

## 📦 Tech
- PHP (Vercel Serverless via `vercel-php`)
- PostgreSQL (Supabase)
- QRCode.js / html5-qrcode / JSZip
