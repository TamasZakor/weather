-- weather_optimized adatbázis létrehozása
CREATE DATABASE IF NOT EXISTS weather_optimized 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_hungarian_ci;

USE weather_optimized;

-- 1. Új cities tábla (javított típusok, PK, UNIQUE)
DROP TABLE IF EXISTS daily_weather;
DROP TABLE IF EXISTS cities;

CREATE TABLE cities (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    region ENUM('Dunántúl', 'Közép-Magyarország', 'Észak és Alföld') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_city_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- 2. Városok betöltése a javított 10-es azonosítóval (Túrkeve)
INSERT INTO cities (id, name, region) VALUES
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

-- 3. Összevont napi mérési tábla (Wide table, natív DATE, PRIMARY KEY, Foreign Key, indexek)
CREATE TABLE daily_weather (
    city_id TINYINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    min_temp DECIMAL(4,1) NOT NULL COMMENT 'Minimum hőmérséklet (°C)',
    max_temp DECIMAL(4,1) NOT NULL COMMENT 'Maximum hőmérséklet (°C)',
    precipitation DECIMAL(5,1) NOT NULL DEFAULT 0.0 COMMENT 'Csapadék (mm)',
    PRIMARY KEY (city_id, date),
    INDEX idx_weather_date (date),
    CONSTRAINT fk_daily_weather_city FOREIGN KEY (city_id) REFERENCES cities (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_hungarian_ci;

-- 4. Adatok áttöltése a régi weather adatbázisból konverzióval
INSERT INTO daily_weather (city_id, date, min_temp, max_temp, precipitation)
SELECT 
    tx.city_id,
    STR_TO_DATE(CAST(tx.date AS CHAR), '%Y%m%d') AS date,
    tn.min_temp,
    tx.max_temp,
    r.precipitation
FROM weather.maximum_temperatures tx
INNER JOIN weather.minimum_temperatures tn 
    ON tx.city_id = tn.city_id AND tx.date = tn.date
INNER JOIN weather.precipitation r 
    ON tx.city_id = r.city_id AND tx.date = r.date;
