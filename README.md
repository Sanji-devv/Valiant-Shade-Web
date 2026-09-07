# Valiant Shade Web

Laravel tabanlı Valiant Shade arazi haritası ve yönetici editörü. Ön yüzde Vite ile derlenen özel bir harita arayüzü (`/valiant-shade`) ve yönetim paneli (`/admin`) bulunur.

## Gereksinimler

Makinede şunlar kurulu olmalı:

- **PHP 8.3+**
- **Composer**
- **Node.js 18+** ve **npm**
- **PostgreSQL** (`.env.example` PostgreSQL kullanır)

## İlk kurulum

### 1. Depoyu klonla

```bash
git clone <repo-url>
cd Valiant-Shade-Web
```

### 2. Ortam dosyasını hazırla

```bash
cp .env.example .env
```

`.env` içinde en az şunları doldur:

```env
DB_PASSWORD=...   # PostgreSQL şifreniz
```

Veritabanı yoksa oluşturun (`.env` değerlerine göre):

```bash
createdb valiant-shade-web
```

### 3. Tek komutla kurulum (önerilen)

Proje kökünde:

```bash
composer setup
```

Bu komut sırasıyla şunları yapar:

1. `composer install`
2. `.env` yoksa oluşturur
3. `php artisan key:generate`
4. `php artisan migrate`
5. `npm install`
6. `npm run build` — ön yüz (harita + app)
7. `npm run build:panels` — admin ve demo-vuexy asset’leri

Ardından örnek veriyi yükleyin ve storage bağlantısını kurun:

```bash
php artisan db:seed
php artisan storage:link
```

### 4. Manuel kurulum

`composer setup` kullanmıyorsanız:

```bash
composer install
cp .env.example .env          # henüz yoksa
php artisan key:generate
php artisan migrate
npm install
npm run build
npm run build:panels
php artisan db:seed
php artisan storage:link
```

> **Not:** `public/static/admin/build` ve `public/static/demo-vuexy/build` git’te yoktur. Bu yüzden `npm run build:panels` mutlaka çalıştırılmalı; aksi halde `/admin` ve `/demo-vuexy` CSS/JS’siz kalır veya hata verir.

---

## Frontend kurulumu (madde madde)

Harita arayüzü (`/valiant-shade`) ve genel ön yüz asset’leri Vite ile derlenir. Projeyi yükleyen kişi frontend tarafında şu adımları izlemeli:

### Adım 1 — Node bağımlılıklarını yükle

Proje kökünde:

```bash
npm install
```

Bu komut `package.json` içindeki tüm bağımlılıkları (`@panzoom/panzoom`, `laravel-vite-plugin`, `vite` vb.) indirir.

### Adım 2 — Production asset’lerini derle

Sunucuda veya ilk çalıştırmada:

```bash
npm run build
```

Bu komut şu dosyaları derler ve `public/build/` altına yazar:

| Kaynak | Açıklama |
|--------|----------|
| `resources/css/valiant-shade/map.css` | Harita stilleri |
| `resources/js/valiant-shade/map.js` | Harita editörü ve görüntüleyici |
| `resources/css/app.css` | Genel ön yüz stilleri |
| `resources/js/app.js` | Genel ön yüz JS |

Harita sayfası Blade şablonunda `@vite(...)` ile bu dosyaları yükler.

Admin paneli veya demo sayfaları da gerekiyorsa ek olarak:

```bash
npm run build:panels
```

### Adım 3 — Geliştirme modunda çalıştır (canlı derleme)

Frontend üzerinde çalışırken iki terminal açın:

**Terminal 1 — Laravel:**

```bash
php artisan serve
```

**Terminal 2 — Vite (hot reload):**

```bash
npm run dev
```

Tarayıcıda `http://localhost:8000` adresine gidin. CSS/JS değişiklikleri kaydettiğinizde Vite sayfayı otomatik yeniler.

Alternatif: Laravel 13 ile birlikte gelen birleşik komut:

```bash
composer dev
```

(Bu komut sunucu + Vite’ı birlikte başlatır.)

### Adım 4 — Harita frontend dosyalarını bil

Valiant Shade haritasının kaynak kodu burada:

```
resources/
├── css/valiant-shade/
│   ├── map.css           # Ana harita teması
│   └── vuexy-icons.css   # Editör ikonları
└── js/valiant-shade/
    ├── map.js            # Giriş noktası
    ├── api.js            # API istekleri
    └── engine/
        ├── MapEngine.js       # Pan/zoom, modlar, kaydet
        ├── EditorPanel.js     # Yan panel UI
        ├── TileLayer.js       # Zemin katmanı
        ├── BuildingLayer.js   # Bina katmanı
        ├── BuildingTypeModal.js
        ├── HistoryManager.js  # Geri al / yinele
        └── ...
```

Vite yapılandırması: `vite.config.js` (harita entry point’leri burada tanımlı).

### Adım 5 — Değişiklik sonrası ne yapmalı?

| Durum | Komut |
|-------|--------|
| Harita CSS/JS değiştirdim, geliştirme modundayım | Kaydet — `npm run dev` otomatik derler |
| Harita CSS/JS değiştirdim, production’a alacağım | `npm run build` |
| Admin panel SCSS/JS değiştirdim | `npm run build:admin` veya `npm run dev:admin` |
| Demo-vuexy asset’lerini değiştirdim | `npm run build:demo-vuexy` veya `npm run dev:demo-vuexy` |
| Her şeyi yeniden derlemek istiyorum | `npm run build && npm run build:panels` |

### Adım 6 — Frontend’i doğrula

1. `php artisan serve` ve `npm run dev` (veya `npm run build` sonrası sadece serve) çalışıyor olmalı.
2. Tarayıcıda aç:
   - **Normal görünüm:** http://localhost:8000/valiant-shade
   - **Editör:** http://localhost:8000/valiant-shade/edit (giriş gerekir)
3. Sayfa kaynak kodunda `@vite` ile yüklenen CSS/JS dosyalarının 404 vermediğini kontrol et.
4. Bina görseli yüklüyorsan `php artisan storage:link` çalıştırılmış olmalı.

---

## Sunucuyu başlatma

```bash
php artisan serve
```

| Adres | Açıklama |
|-------|----------|
| http://localhost:8000 | Ana sayfa |
| http://localhost:8000/valiant-shade | Harita — ziyaretçi görünümü |
| http://localhost:8000/valiant-shade/edit | Harita editörü (yönetici) |
| http://localhost:8000/login | Yönetici girişi |
| http://localhost:8000/admin | Yönetim paneli |
| http://localhost:8000/demo-vuexy | Vuexy referans demo (dokunulmaz) |

### Giriş bilgileri (seed sonrası)

| Alan | Değer |
|------|--------|
| E-posta | `admin@valiant-shade.test` |
| Şifre | `password` |

---

## Geliştirme rehberi

### Ön yüz (Valiant Shade haritası)

- View: `resources/views/web/valiant-shade/`
- Route: `routes/web.php`
- API: `routes/web.php` içindeki `/api/maps/...` uçları

### Yönetim paneli

- View: `resources/views/admin/`
- Controller: `app/Http/Controllers/Admin/`
- Route: `routes/admin.php`
- Tasarım referansı: `/demo-vuexy`

### Dokunulmaması gerekenler

- `resources/views/demo-vuexy/` — referans demo
- `app/DemoVuexy/` — demo backend
- `resources/demo-vuexy/` — demo kaynak asset’leri

---

## Git pull sonrası

Başka bir geliştirici veya siz `git pull` yaptıktan sonra:

```bash
composer install          # PHP bağımlılıkları değiştiyse
npm install               # package.json değiştiyse
npm run build             # harita / ön yüz asset’leri
npm run build:panels      # admin / demo asset’leri (gerekirse)
php artisan migrate       # yeni migration varsa
```

---

## Lisans

[Laravel MIT lisansı](https://opensource.org/licenses/MIT) altında dağıtılır.
