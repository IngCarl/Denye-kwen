# 🏪 DENYE KWEN — Sistèm Jesyon Biznis v2.0

Sistèm konplè jesyon magazen jeneral (grocery/supermarket) an **Kreyòl Ayisyen 100%**, ak PHP, MySQL, HTML, CSS ak JavaScript. Deviz: **HTG (Goud Ayisyen)**.

---

## 📋 Egzijans

| Konpozan | Vèsyon minimòm |
|---|---|
| XAMPP (oswa WAMP/LAMP) | Nenpòt vèsyon resan |
| PHP | 7.4+ |
| MySQL / MariaDB | 5.7+ / 10.4+ |
| Navigatè | Chrome, Edge, Firefox (resan) |

---

## 🚀 Enstalasyon Etap-pa-Etap

### Etap 1 — Enstale XAMPP
1. Telechaje XAMPP sou [apachefriends.org](https://www.apachefriends.org)
2. Enstale li epi louvri **XAMPP Control Panel**
3. Demare **Apache** ak **MySQL**

### Etap 2 — Kopye dosye pwojè a
Kopye tout dosye `denye-kwen/` la nan:
```
C:\xampp\htdocs\denye-kwen
```
(Sou Linux/Mac: `/opt/lampp/htdocs/denye-kwen`)

### Etap 3 — Kreye baz done a
1. Louvri navigatè ou sou: `http://localhost/phpmyadmin`
2. Klike sou tab **Import** (Enpòte)
3. Chwazi fichye `sql/denye_kwen.sql`
4. Klike **Go** (Ale)

> ✅ Sa ap kreye baz done `denye_kwen` ak 11 tab + tout done demo yo otomatikman.

**Oswa ak liy kòmand:**
```bash
mysql -u root -p < sql/denye_kwen.sql
```

### Etap 4 — Konfigirasyon (si nesesè)
Louvri `config/database.php` epi ajiste si MySQL ou gen modpas:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'denye_kwen');
define('DB_USER', 'root');
define('DB_PASS', '');   // ← mete modpas MySQL ou la si genyen
```

### Etap 5 — Louvri sistèm nan
Ale sou:
```
http://localhost/denye-kwen/
```

---

## 🔑 Kredansyèl Demo

| Wòl | Username | Modpas | Aksè |
|---|---|---|---|
| 👑 Admin | `admin` | `admin123` | Tout sistèm nan (SOF POS) |
| 💰 Kesye | `kesye` | `kesye123` | POS, kliyan, pwòp vant li |
| 💰 Kesye 2 | `kesye2` | `kesye123` | POS, kliyan, pwòp vant li |
| 🔍 Oditè | `odite` | `odite123` | Gade sèlman (read-only) |

---

## 👥 Wòl ak Pèmisyon

| Fonksyon | Admin | Kesye | Oditè |
|---|:-:|:-:|:-:|
| POS (fè vant) | ❌ | ✅ | ❌ |
| Sèvis (anrejistre + resi) | ❌ | ✅ | 👁️ |
| Pwodwi & Stòk (CRUD) | ✅ | ❌ | 👁️ |
| Kliyan (CRUD) | ✅ | ✅ | 👁️ |
| Founisè (CRUD) | ✅ | ❌ | 👁️ |
| Depans (CRUD) | ✅ | ❌ | 👁️ |
| Rapò | ✅ | ❌ | ✅ |
| Itilizatè (CRUD) | ✅ | ❌ | 👁️ |
| Paramèt / Backup | ✅ | ❌ | ❌ |
| Jounal aktivite | ✅ | ❌ | ✅ |

---

## 📁 Estrikti Dosye

```
denye-kwen/
├── config/
│   └── database.php        → Koneksyon PDO + helpers (response, checkAuth, checkRole, logActivity)
├── api/
│   ├── auth.php            → Login, logout, check session
│   ├── products.php        → CRUD pwodwi + kategori + barcode lookup
│   ├── clients.php         → CRUD kliyan
│   ├── suppliers.php       → CRUD founisè
│   ├── expenses.php        → CRUD depans + stats
│   ├── sales.php           → Kreye vant (transaction), istorik, anile
│   ├── services.php        → Sèvis (Kesye): rabè opsyonèl + resi
│   ├── users.php           → CRUD itilizatè
│   ├── reports.php         → Dashboard, rapò finansye, jounal, backup
│   └── settings.php        → Paramèt biznis + zòn danje
├── assets/
│   ├── css/style.css       → Style global (responsive, print 80mm)
│   └── js/app.js           → API client (fetch async/await) + itilite
├── sql/
│   └── denye_kwen.sql      → Schema 11 tab + done demo
├── index.html              → Login
├── admin.html              → Dashboard Admin
├── kesye.html              → Dashboard Kesye
├── odite.html              → Dashboard Oditè (read-only)
├── pos.html                → POS (Kesye sèlman)
├── pwodwi.html             → Pwodwi & Stòk (Admin)
├── kliyan.html             → Kliyan & Founisè (Admin + Kesye)
├── depans.html             → Depans (Admin)
├── rapo.html               → Rapò (Admin + Oditè)
└── paramet.html            → Paramèt & Itilizatè (Admin)
```

---

## 🔌 API Endpoints

Tout repons yo an JSON: `{ "success": true/false, "data": {...}, "message": "..." }`

### 🔐 auth.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| POST | `api/auth.php?action=login` | Konekte `{username, password}` |
| POST | `api/auth.php?action=logout` | Dekonekte |
| GET | `api/auth.php?action=check` | Verifye sesyon |

### 📦 products.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/products.php` | Lis tout pwodwi aktif |
| GET | `api/products.php?id=1` | Yon pwodwi |
| GET | `api/products.php?barcode=740...` | Chèche pa barcode |
| GET | `api/products.php?kategori=1` | Lis kategori |
| POST | `api/products.php` | Kreye (Admin) |
| PUT | `api/products.php` | Modifye (Admin) |
| DELETE | `api/products.php?id=1` | Soft delete (Admin) |

### 👥 clients.php / 🚚 suppliers.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/clients.php` | Lis kliyan |
| POST/PUT | `api/clients.php` | Kreye/Modifye (Admin+Kesye) |
| DELETE | `api/clients.php?id=1` | Soft delete (Admin) |
| GET/POST/PUT/DELETE | `api/suppliers.php` | Menm jan, Admin sèlman |

### 💵 sales.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| POST | `api/sales.php` | Kreye vant (Kesye sèlman, MySQL transaction) `{client_id, items:[{product_id, kantite}], rabe, kob_resevwa, mwayen_peman}` |
| GET | `api/sales.php?limit=50` | Istorik (Kesye wè pwòp vant li sèlman) |
| GET | `api/sales.php?id=1` | Detay yon vant + atik yo |
| GET | `api/sales.php?kesye_stats=1` | Stats jounen kesye a |
| PUT | `api/sales.php` | Anile/retounen `{id, estati}` (Admin, remete stòk) |

### 💸 expenses.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/expenses.php` | Lis depans |
| GET | `api/expenses.php?stats=1` | Stats (jodi, mwa, total) |
| POST/PUT/DELETE | `api/expenses.php` | CRUD (Admin) |

### 👤 users.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/users.php` | Lis itilizatè (Admin+Odite) |
| POST/PUT | `api/users.php` | Kreye/Modifye (Admin, bcrypt) |
| DELETE | `api/users.php?id=2` | Dezaktive (Admin) |

### 📊 reports.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/reports.php?type=dashboard` | Stats dashboard |
| GET | `api/reports.php?type=finans&debi=2026-06-01&fen=2026-06-30` | Rapò finansye |
| GET | `api/reports.php?type=logs&limit=15` | Jounal aktivite |
| GET | `api/reports.php?type=products` | Stats pwodwi |
| GET | `api/reports.php?type=backup` | Backup JSON konplè (Admin) |

### 🤝 services.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| POST | `api/services.php` | Kreye sèvis (Kesye sèlman) `{deskripsyon, pri, rabe?, moun_bay_sevis, jou?, dat, mwayen_peman}` — rabè opsyonèl, dediksyon otomatik (total = pri − rabè), nimewo resi SVC-YYYYMMDD-XXXX |
| GET | `api/services.php?limit=10` | Lis (Kesye wè pwòp sèvis li sèlman) |
| GET | `api/services.php?id=1` | Detay yon sèvis (pou resi) |
| GET | `api/services.php?kesye_stats=1` | Stats sèvis jounen kesye a |
| DELETE | `api/services.php?id=1` | Efase (Admin) |

### ⚙️ settings.php
| Metòd | Endpoint | Deskripsyon |
|---|---|---|
| GET | `api/settings.php` | Li paramèt |
| PUT | `api/settings.php` | Modifye (Admin) |
| DELETE | `api/settings.php?reset=1` | ⚠️ Efase done tranzaksyonèl (Admin) |

---

## 🖨️ Enprimant 3nStar RPT006

- Modal koneksyon nan **Dashboard Admin** (USB / Bluetooth / Serial via WebUSB, Web Bluetooth, Web Serial — Chrome/Edge)
- **Fallback otomatik**: `window.print()` ak CSS `@media print` fòma **80mm tèmal**
- Resi a gen: ant-tèt biznis, # fakti, dat/lè, kliyan, kesye, lis atik, total, peman, monen, mesaj pye

## ⌨️ Raccourci Klavye POS

| Touch | Aksyon |
|---|---|
| **F1** | Fokis sou rechèch |
| **F2** | Louvri scanner barcode |
| **F9** | Louvri modal peman |

---

## 🔐 Sekirite

- ✅ Modpas hash ak `password_hash()` (bcrypt)
- ✅ PDO prepared statements (kont SQL injection)
- ✅ PHP Sessions ak timeout 24h + `session_regenerate_id`
- ✅ Verifikasyon wòl (`checkRole`) nan chak endpoint
- ✅ Soft delete (`aktif=0`) olye efasman fizik
- ✅ Log otomatik tout aksyon nan `activity_logs`
- ✅ MySQL Transaction (BEGIN/COMMIT/ROLLBACK) pou vant + `FOR UPDATE` sou stòk
- ✅ Escape output (`htmlspecialchars` + `esc()` bò kliyan)
- ✅ CORS headers apwopriye

---

## ❓ Pwoblèm Kouran

| Pwoblèm | Solisyon |
|---|---|
| "Pa ka kominike ak sèvè a" | Verifye Apache + MySQL ap mache nan XAMPP |
| "Erè koneksyon baz done" | Verifye DB_USER/DB_PASS nan `config/database.php` epi baz done a enpòte |
| Login pa mache | Verifye ou enpòte `sql/denye_kwen.sql` la nèt |
| Karaktè Kreyòl parèt mal | Baz done a dwe `utf8mb4_unicode_ci` (script SQL la fè sa otomatikman) |
| Enprimant pa konekte | Itilize Chrome/Edge; sinon fallback `window.print()` ap mache |

---

**DENYE KWEN v2.0** — Fèt ak ❤️ pou biznis ayisyen. 🇭🇹
