<?php

use App\ValiantShade\Support\WorldCalendarCatalog;

/*
|--------------------------------------------------------------------------
| Valiant Shade evren takvimi
|--------------------------------------------------------------------------
|
| Gün ve ay adları enum sınıflarından üretilir (InWorldWeekday, InWorldMonth).
| Yıl: gregorian + year_offset → evren yılı (ör. 2026 → 631).
|
| Pazar-Vonyas · Kasım-Leos · … tam liste: app/ValiantShade/Enums/
|
*/

return WorldCalendarCatalog::toConfigArray();
