# Adatbázis Struktúra és Tartalom Módosítási Javaslatok (db-suggestions.md)

Ez a dokumentum a meteorológiai adatbázis (`weather`) felépítésének, adatmodelljének és tartalmának szakmai elemzését, valamint a feltárt hibák javítási javaslatait tartalmazza.

---

## 1. Kritikus Tartalmi és Adatintegritási Hibák

### 1.1. 10-es azonosítójú város hibás neve és régiója (Szombathely vs. Túrkeve)
* **Probléma:** A `weather.cities` táblában a 10-es azonosítójú rekord így szerepel:
  ```sql
  (10, 'Szombathely', 'Észak és Alföld')
  ```
  Ez két okból is súlyos hiba:
  1. Szombathely a 9-es ID-val már szerepel `Dunántúl` régióval (ami földrajzilag helyes).
  2. A `migrations/003_import_weather_data.py` és a forrás CSV fájlok (`*_o_Turkeve_19012023.csv`) alapján a 10-es város valójában **Túrkeve** (Jász-Nagykun-Szolnok vármegye, Észak és Alföld régió).
* **Következmény:** 
  - A felhasználói felületen kétszer jelenik meg Szombathely (különböző régiókkal).
  - A régiós lekérdezés során az "Észak és Alföld" aggregációjába Túrkeve adatai helyett Szombathelyként jelennek meg a mérések, torzítva az eredményeket.
* **Javasolt javítás:**
  ```sql
  UPDATE weather.cities 
  SET name = 'Túrkeve', region = 'Észak és Alföld' 
  WHERE id = 10;
  ```

### 1.2. Egyediségi megszorítás (UNIQUE Constraint) hiánya a városneveken
* **Probléma:** A `cities.name` oszlopon nincs `UNIQUE` megszorítás.
* **Következmény:** Bármikor bekerülhetnek azonos nevű városok duplikációként.
* **Javasolt javítás:**
  ```sql
  ALTER TABLE weather.cities ADD CONSTRAINT uq_cities_name UNIQUE (name);
  ```

---

## 2. Strukturális és Sémahibák (Keys & Constraints)

### 2.1. Elsődleges kulcsok (PRIMARY KEY) teljes hiánya
* **Probléma:** Egyetlen mérési táblának sincs elsődleges kulcsa (`maximum_temperatures`, `minimum_temperatures`, `precipitation`).
* **Következmény:**
  - InnoDB tárolómotor esetén, ha nincs explicit elsődleges kulcs, a MySQL egy rejtett, 6 bájtos sorazonosítót (`GEN_CLUST_INDEX`) hoz létre soronként, ami felesleges lemezterületet foglal és nem használható keresésre.
  - Elméletileg ugyanarra a napra és ugyanarra a városra tetszőleges számú duplikált mérési rekord beszúrható.
* **Javasolt javítás:**
  Összetett elsődleges kulcs (`city_id`, `date`) definiálása mindhárom mérési táblában:
  ```sql
  ALTER TABLE weather.maximum_temperatures ADD PRIMARY KEY (city_id, date);
  ALTER TABLE weather.minimum_temperatures ADD PRIMARY KEY (city_id, date);
  ALTER TABLE weather.precipitation ADD PRIMARY KEY (city_id, date);
  ```

### 2.2. Idegen kulcsok (Foreign Keys) teljes hiánya
* **Probléma:** A `city_id` mezők a mérési táblákban nincsenek idegen kulcsként összekötve a `cities.id` oszloppal.
* **Következmény:**
  - Árva rekordok (Orphan records) keletkezhetnek olyan `city_id`-val, amihez nem tartozik létező város.
  - Egy város törlése esetén a mérési adatok integritása sérül.
* **Javasolt javítás:**
  ```sql
  ALTER TABLE weather.maximum_temperatures 
    ADD CONSTRAINT fk_max_temp_city FOREIGN KEY (city_id) REFERENCES weather.cities(id) ON DELETE RESTRICT;

  ALTER TABLE weather.minimum_temperatures 
    ADD CONSTRAINT fk_min_temp_city FOREIGN KEY (city_id) REFERENCES weather.cities(id) ON DELETE RESTRICT;

  ALTER TABLE weather.precipitation 
    ADD CONSTRAINT fk_precip_city FOREIGN KEY (city_id) REFERENCES weather.cities(id) ON DELETE RESTRICT;
  ```

---

## 3. Indexelési Stratégia és Lekérdezés Optimalizálás

* **Jelenlegi állapot:** Az adatbázisban **0 db index** található a mérési adatokon (`Index_length = 0`).
* **Hatás a lekérdezésekre:**
  - Amikor egy felhasználó lekérdezi egy város 1 éves intervallumát, a MySQL **FULL TABLE SCAN**-t végez mindhárom táblán (~450 000 sor táblánként, összesen >1.3 millió sor vizsgálata másodpercenként).
  - A 3 tábla összekapcsolása (`INNER JOIN ... ON city_id AND date`) index nélkül **Block Nested-Loop / Hash Join**-t igényel, ami magas CPU és memória használatot okoz.
* **Javasolt indexek:**
  1. **Összetett Clustered Index (Primary Key):** `(city_id, date)` – azonnal kiszolgálja a város alapú időintervallumos szűréseket és a JOIN feltételeket.
  2. **Dátum index:** `INDEX idx_date (date)` – a régió alapú lekérdezésekhez elengedhetetlen, ahol a szűrés elsődlegesen dátumra és a csatolt városok régiójára történik.

---

## 4. Adattípusok és Tárhely Optimalizáció (Data Types Footprint)

A jelenlegi adattípusok feleslegesen pazarolják a memóriát (Buffer Pool) és a lemezterületet:

| Tábla | Oszlop | Jelenlegi típus | Javasolt típus | Megtakarítás / Érv |
|---|---|---|---|---|
| `cities` | `id` | `INT` (4 bájt) | `TINYINT UNSIGNED` (1 bájt) | Magyarország településszáma vagy a mérőállomások száma elfér (0–255), AUTO_INCREMENT támogatással. |
| `cities` | `name` | `VARCHAR(255)` | `VARCHAR(100)` | Nincs 100 karakternél hosszabb településnév. |
| `*temperatures, precipitation` | `city_id` | `INT` (4 bájt) | `TINYINT UNSIGNED` (1 bájt) | 450 000 sornál táblánként **1.35 MB memóriamegtakarítás** pusztán ebből az oszlopból. |
| `*temperatures, precipitation` | `date` | `BIGINT` (8 bájt) | `DATE` (3 bájt) | **62.5% méretcsökkenés** a dátumoszlopon! Valódi SQL dátumfüggvények (`YEAR()`, `MONTH()`, `BETWEEN`) és dátumvalidáció. |
| `*temperatures, precipitation` | `*_temp`, `precipitation` | `FLOAT` (4 bájt) | `DECIMAL(4,1)` / `DECIMAL(5,1)` | A meteorológiai adatok 1 tizedesjegy pontosságúak (pl. 21.4 °C). A lebegőpontos `FLOAT` kerekítési pontatlanságokat okozhat aggregációknál (`AVG`, `SUM`), a `DECIMAL` fixpontos és egzakt. |

> [!NOTE]
> Pusztán a `date` és `city_id` típusok optimalizálásával a mérési táblák mérete **~40%-kal csökkenthető**, ami arányosan növeli a MySQL InnoDB Buffer Pool hatékonyságát (több adat fér be memóriába, kevesebb lemez I/O).

---

## 5. Architekturális Továbbfejlesztés: Összevont Napi Tábla (Wide Table)

### Jelenlegi modell (3 külön tábla):
A rendszer jelenleg 3 független táblában tárolja a maximumot, minimumot és csapadékot:
- `maximum_temperatures`
- `minimum_temperatures`
- `precipitation`

### Hátrányok:
- Minden napi adatlekéréshez **2 db INNER JOIN** szükséges.
- Háromszoros az InnoDB tranzakciós és sorfejléc-overhead (3 sor jön létre ugyanahhoz a méréshez).
- 3 külön indexfát kell karbantartani.

### Javasolt alternatíva (Wide Table denormalizáció):
Egyetlen konszolidált `weather_daily_measurements` tábla:
```sql
CREATE TABLE weather.weather_daily_measurements (
    city_id TINYINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    min_temp DECIMAL(4,1) NOT NULL,
    max_temp DECIMAL(4,1) NOT NULL,
    precipitation DECIMAL(5,1) NOT NULL DEFAULT 0.0,
    PRIMARY KEY (city_id, date),
    INDEX idx_date (date),
    CONSTRAINT fk_measurements_city FOREIGN KEY (city_id) REFERENCES weather.cities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
```

**Előnyök:**
- **Zero JOIN:** Egy város adatainak lekérésekor nincs szükség JOIN-ra, a lekérdezés azonnali index seek lesz.
- Jelentősen kisebb lemezterület és gyorsabb mentés / visszaállítás.

---

## 6. Nagy Léptékű Adatkezelés: Particionálás (Partitioning)

A meteorológiai adatok tipikus **Time-Series** (idősoros) jellegűek:
- 120+ év adatai folyamatosan növekednek.
- A lekérdezések szinte kivétel nélkül dátumintervallumra (`startDate`, `endDate`) szűrnek.

### Javaslat:
**Év vagy Évtized alapú RANGE partícionálás** a `date` oszlop mentén:
```sql
ALTER TABLE weather.weather_daily_measurements
PARTITION BY RANGE (YEAR(date)) (
    PARTITION p_early VALUES LESS THAN (1920),
    PARTITION p_1920s VALUES LESS THAN (1940),
    PARTITION p_1940s VALUES LESS THAN (1960),
    PARTITION p_1960s VALUES LESS THAN (1980),
    PARTITION p_1980s VALUES LESS THAN (2000),
    PARTITION p_2000s VALUES LESS THAN (2020),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```
* **Eredmény:** A MySQL lekérdezés-optimalizálója kihasználja a **Partition Pruning** képességet: ha a felhasználó pl. 1950-es évet kér le, a motor kizárólag a `p_1940s` partíciót olvassa be a lemezről, a többi évtizedet teljesen figyelmen kívül hagyja.

---

## 7. Összefoglaló Javaslati Script (Refaktorált Séma)

Amennyiben az adatbázis sémáját modernizálni kívánjuk a fentiek alapján:

```sql
-- 1. Városok tábla javítása
CREATE TABLE weather.cities_new (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    region ENUM('Dunántúl', 'Közép-Magyarország', 'Észak és Alföld') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_city_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- 2. Korrigált városadatok (Túrkeve beillesztésével)
INSERT INTO weather.cities_new (id, name, region) VALUES
    (1, 'Budapest', 'Közép-Magyarország'),
    (2, 'Debrecen', 'Észak és Alföld'),
    (3, 'Keszthely', 'Dunántúl'),
    (4, 'Miskolc', 'Észak és Alföld'),
    (5, 'Nyíregyháza', 'Észak és Alföld'),
    (6, 'Pécs', 'Dunántúl'),
    (7, 'Sopron', 'Dunántúl'),
    (8, 'Szeged', 'Észak és Alföld'),
    (9, 'Szombathely', 'Dunántúl'),
    (10, 'Túrkeve', 'Észak és Alföld');

-- 3. Összevont napi mérési tábla
CREATE TABLE weather.daily_weather (
    city_id TINYINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    min_temp DECIMAL(4,1) NOT NULL COMMENT 'Minimum hőmérséklet (°C)',
    max_temp DECIMAL(4,1) NOT NULL COMMENT 'Maximum hőmérséklet (°C)',
    precipitation DECIMAL(5,1) NOT NULL DEFAULT 0.0 COMMENT 'Csapadék (mm)',
    PRIMARY KEY (city_id, date),
    INDEX idx_weather_date (date),
    CONSTRAINT fk_daily_weather_city FOREIGN KEY (city_id) REFERENCES weather.cities_new (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;
```

---

## 8. Elkészült Példány és Exportált Dump

A fenti optimalizációk és javítások élesben is megvalósításra kerültek egy dedikált `weather_optimized` nevű adatbázisban a MySQL szerveren:
- A táblák sémája a fenti javaslatok szerint épült fel (`TINYINT UNSIGNED`, `DATE`, `DECIMAL(4,1)`, `PRIMARY KEY`, `INDEX`, `FOREIGN KEY`).
- A `cities` táblában a 10-es azonosító sikeresen korrigálva lett: **Túrkeve** (`Észak és Alföld`).
- Mind a **449 250 mérési rekord** áttöltésre és konvertálásra került a konszolidált `daily_weather` táblába.
- Az adatbázisról teljes, azonnal importálható SQL mentés készült a gyökérkönyvtárba:
  - **Fájl:** [`weather_optimized.sql`](./weather_optimized.sql) (~27.5 MB)

---

## 9. Valós Teljesítmény- és Tárhelymérések (Eredeti vs. Optimalizált)

A javaslatok validálására egzakt méréseket végeztünk a futó MySQL 8.0 környezetben az `information_schema` és az `EXPLAIN ANALYZE` profilozó eszköz segítségével.

### 9.1. Tárhely és Memória Lábnyom (Storage & Memory Footprint)

```sql
SELECT 
    table_schema AS 'Database',
    COUNT(table_name) AS 'Tables',
    ROUND(SUM(data_length) / 1024 / 1024, 2) AS 'Data (MB)',
    ROUND(SUM(index_length) / 1024 / 1024, 2) AS 'Index (MB)',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Total (MB)'
FROM information_schema.tables
WHERE table_schema IN ('weather', 'weather_optimized')
GROUP BY table_schema;
```

**Mérési eredmény:**

| Metrika | Eredeti séma (`weather`) | Optimalizált séma (`weather_optimized`) | Különbözet / Eredmény |
|---|---|---|---|
| **Táblák száma** | 4 db | 2 db | -50% séma komplexitás |
| **Nyers adatméret** | **58.66 MB** | **25.56 MB** | **-56.4% (-33.1 MB megtakarítás)** |
| **Indexméret** | **0.00 MB** *(nincs index)* | **7.53 MB** | Teljes B-fa lefedettség |
| **Összes lemezterület** | **58.66 MB** | **33.09 MB** | **-43.6% megtakarítás** |

> [!TIP]
> Az új séma nemcsak **43.6%-kal kevesebb lemezterületet** foglal, de mivel az adattábla mérete felére csökkent (58.66 MB ➔ 25.56 MB), a teljes adathalmaz **kényelmesen elfér az InnoDB Buffer Pool memóriájában**, radikálisan csökkentve a fizikai lemez I/O terhelést.

---

### 9.2. Lekérdezési Sebesség és Erőforrás-felhasználás (Benchmark)

Az alábbi lekérdezések futási idejét mértük `EXPLAIN ANALYZE` segítségével:

#### 1. Mérés: Egy város 1 éves intervalluma (Budapest, 1901. év – 365 sor)

**Eredeti séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    tx.date, 
    tn.min_temp, 
    tx.max_temp, 
    ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp, 
    r.precipitation
FROM weather.maximum_temperatures tx
INNER JOIN weather.minimum_temperatures tn ON tx.city_id = tn.city_id AND tx.date = tn.date
INNER JOIN weather.precipitation r ON tx.city_id = r.city_id AND tx.date = r.date
WHERE tx.city_id = 1 AND tx.date >= 19010101 AND tx.date <= 19011231;
```
- **Eredeti séma (`weather`):**
  - Futási idő: **1029.0 ms (1.03 másodperc)**
  - Végrehajtási terv: 3 db **Full Table Scan** + 2 db Hash Join.
  - Beolvasott sorok száma: **1 347 750 sor** (449 250 sor × 3 tábla).

**Optimalizált séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    date, 
    min_temp, 
    max_temp, 
    ROUND((min_temp + max_temp) / 2, 2) AS avgTemp, 
    precipitation
FROM weather_optimized.daily_weather
WHERE city_id = 1 AND date >= '1901-01-01' AND date <= '1901-12-31';
```
- **Optimalizált séma (`weather_optimized`):**
  - Futási idő: **0.262 ms**
  - Végrehajtási terv: **Index Range Scan** a `PRIMARY` kulcson (`city_id = 1 AND '1901-01-01' <= date <= '1901-12-31'`).
  - Beolvasott sorok száma: **pontosan 365 sor** (0 db felesleges sor).
- **Különbözet:** **~3900-szoros sebességnövekedés**, 99.97%-os I/O csökkenés.

#### 2. Mérés: Egy város teljes archívuma (Budapest, 1901–2023 – 44 925 sor)

**Eredeti séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    tx.date, 
    tn.min_temp, 
    tx.max_temp, 
    ROUND((tn.min_temp + tx.max_temp) / 2, 2) AS avgTemp, 
    r.precipitation
FROM weather.maximum_temperatures tx
INNER JOIN weather.minimum_temperatures tn ON tx.city_id = tn.city_id AND tx.date = tn.date
INNER JOIN weather.precipitation r ON tx.city_id = r.city_id AND tx.date = r.date
WHERE tx.city_id = 1;
```
- **Eredeti séma:** **1046.0 ms (1.05 másodperc)** (3× Full Table Scan).

**Optimalizált séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    date, 
    min_temp, 
    max_temp, 
    ROUND((min_temp + max_temp) / 2, 2) AS avgTemp, 
    precipitation
FROM weather_optimized.daily_weather
WHERE city_id = 1;
```
- **Optimalizált séma:** **55.1 ms** (Clustered Index seek a PRIMARY kulcson).
- **Különbözet:** **~19-szeres gyorsulás**.

#### 3. Mérés: Régiós aggregáció 1 évre (Dunántúl régió, 1901. év)

**Eredeti séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    tx.date,
    ROUND(AVG(tn.min_temp), 2) AS minTemp,
    ROUND(AVG(tx.max_temp), 2) AS maxTemp,
    ROUND(AVG((tn.min_temp + tx.max_temp) / 2), 2) AS avgTemp,
    ROUND(AVG(r.precipitation), 2) AS precipitation
FROM weather.cities c
INNER JOIN weather.maximum_temperatures tx ON c.id = tx.city_id
INNER JOIN weather.minimum_temperatures tn ON c.id = tn.city_id AND tx.date = tn.date
INNER JOIN weather.precipitation r ON c.id = r.city_id AND tx.date = r.date
WHERE c.region = 'Dunántúl'
  AND tx.date >= 19010101 
  AND tx.date <= 19011231
GROUP BY tx.date
ORDER BY tx.date ASC;
```
- **Eredeti séma:**
  - Futási idő: **946.0 ms**
  - Végrehajtási terv: 4 tábla scan + ideiglenes táblás rendezés (`Aggregate using temporary table`).

**Optimalizált séma SQL:**
```sql
EXPLAIN ANALYZE
SELECT 
    w.date,
    ROUND(AVG(w.min_temp), 2) AS minTemp,
    ROUND(AVG(w.max_temp), 2) AS maxTemp,
    ROUND(AVG((w.min_temp + w.max_temp) / 2), 2) AS avgTemp,
    ROUND(AVG(w.precipitation), 2) AS precipitation
FROM weather_optimized.cities c
INNER JOIN weather_optimized.daily_weather w ON c.id = w.city_id
WHERE c.region = 'Dunántúl'
  AND w.date >= '1901-01-01' 
  AND w.date <= '1901-12-31'
GROUP BY w.date
ORDER BY w.date ASC;
```
- **Optimalizált séma:**
  - Futási idő: **14.4 ms**
  - Végrehajtási terv: Másodlagos dátumindex tartományi pásztázás (`idx_weather_date`), csak az 1901-es napok beolvasása.
- **Különbözet:** **~65-szörös sebességnövekedés**.

---

### 9.3. Összegző Benchmarking Táblázat

| Lekérdezési forgatókönyv | Eredeti séma (`weather`) | Optimalizált séma (`weather_optimized`) | Gyorsulási arány |
|---|---|---|---|
| **Városi időjárás (1 év / 365 sor)** | 1 029.00 ms | **0.26 ms** | **~3 900×** 🚀 |
| **Városi időjárás (123 év / 44 925 sor)** | 1 046.00 ms | **55.10 ms** | **~19×** 🚀 |
| **Régió aggregáció (1 év)** | 946.00 ms | **14.40 ms** | **~65×** 🚀 |
| **Memória / Adatméret lemezen** | 58.66 MB | **25.56 MB** | **-56.4%** 💾 |
| **Full Table Scan terhelés** | Mindig 1.34 millió sor | **0 sor (csak index seek)** | **100% megszüntetve** |


