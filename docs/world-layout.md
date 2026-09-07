# Dünya Haritası — Katmanlar

## Oyuncu akışı

```
/  (Feldan Krallığı görseli)
  └─ gizli tık: Isonburg bölgesi
       /isonburg  (Isonburg Krallığı görseli)
         ├─ gizli tık: Isonburg şehri → /isonburg/sehir
         └─ gizli tık: kulübe → /valiant-shade
              /isonburg/sehir  (şehir görseli + gizli tile harita)
                └─ gizli tık: Ebonwings HQ → /valiant-shade
                     /valiant-shade  (lonca görsel harita + menüden binalar)
                       └─ isteğe bağlı: İsimli harita (tile katman)
```

## Görseller

| Dosya | Konum |
|-------|--------|
| Feldan Krallığı (önizleme) | `public/static/valiant-shade/world/feldan-kralligi-preview.jpg` (~79 KB) |
| Feldan Krallığı (HD) | `public/static/valiant-shade/world/feldan-kralligi-hd.jpg` (zoom ≥ %115) |
| Isonburg Krallığı | `public/static/valiant-shade/world/isonburg-kralligi.jpg` |
| Isonburg Şehri | `public/static/valiant-shade/world/isonburg-sehri.jpg` |
| Valiant Shade (önizleme) | `public/static/valiant-shade/world/valiant-shade-arazi-preview.jpg` |
| Valiant Shade (HD) | `public/static/valiant-shade/world/valiant-shade-arazi-hd.jpg` |

## Tıklanabilir alanlar

Koordinatlar `config/world-maps.php` içinde yüzde olarak tanımlı. Gizli alanlar normalde görünmez; üzerine gelince hafif çerçeve çıkar.

## Tile haritalar (editör)

| Slug | URL | Açıklama |
|------|-----|----------|
| `valiant-shade` | `/valiant-shade` | Lonca arazisi (görsel + gizli tile) |
| `isonburg-sehir` | `/isonburg/sehir` (gizli katman) | Şehir içi binalar |
| `isonburg` | `/isonburg/arazi` | Krallık arazi (geliştirici) |

Isonburg şehir sayfasında **Harita katmanı** butonu ile alttaki tile harita görünür hale gelir.

Valiant Shade sayfasında varsayılan görünüm **görsel harita + bina menüsü**dür. **İnteraktif harita** ile tile katman açılır (editör: `/valiant-shade/edit`).

Bina listesi `config/world-maps.php` → `valiant-shade-guild.buildings`.

## Geliştirici

- `/home` — editör bağlantıları
- Hotspot koordinatlarını ayarlamak için `config/world-maps.php` düzenleyin
