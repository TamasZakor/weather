# Adiumsoft PHP / React gyakorlati feladat

## Előkészületek

Legyenek feltelepítve az alábbi programok:

- [Docker](https://docs.docker.com/get-started/get-docker/) és [Docker Compose](https://docs.docker.com/compose/install/)
- `git` valamilyen formájában (pl. [Git bash](https://git-scm.com/downloads/win))

A leírás további részében szereplő parancsok `bash`-hoz készültek, ebben fognak
helyesen működni. Használható természetesen `cmd`, PowerShell vagy bármilyen
egyéb shell is, de ebben az esetben valószínüleg a parancsok módosítására lesz
szükség. 

Ha még nem tetted meg, töltsd le magadhoz a repository-t:

```sh
git clone https://bitbucket.org/adiumsoftdev/takehome.git
```

Hozd létre a `.env` fájlt - ez tartalmazza a Docker containerek beállításait:

```sh
cd takehome
cp .env.example .env
```

## Feladat

A feladat során meteorológiai adatokból kimutatást készítő fullstack alkalmazást
kell elkészítened. A részfeladatok megoldási sorrendje tetszőleges. A
végeredmény ellenőrzése a `prod` és `db` Docker containerek felépítésével,
indításával, használatával, illetve a forráskód ellenőrzésével fog zajlani. A
feladatot tehát akkor add át, mikor megbizonyosodtál róla, hogy az alábbi
parancs lefut,

```sh
docker compose up prod db
```

az elkészült program elérhető a `http://localhost:8300` címen, és megfelelően
működik. Csak az `api/` és `client/` mappák tartalmát, a `db-suggestions.md`
fájlt, illetve szükség esetén a `docker-compose.yaml` fájl tartalmát módosítsd!


### Fejlesztői környezet, Docker

Készítsd elő a feladathoz szükséges adatokat! Az adatok a `migrations/`
könyvtárban, a `data.zip` fájlban találhatóak. Csomagold ki ugyanebbe a mappába
atartalmát. A `db` container indításakor ezek a fájlok automatikusan be lesznek
töltve.

```sh
cd migrations
unzip data.zip
```

Indítsd el a fejlesztéshez szükséges Docker containereket:

```sh
docker compose up api-dev client-dev db
```

Sikeres indítás után győződj meg róla, hogy lefutottak-e a külső csomagokat
telepítő scriptek (`composer install` és `npm ci`)! A Docker folyamatnak legyen
írási joga az `api/` és `client/` mappákhoz, mert a telepítő scriptek írás
műveletet végeznek. Ha ezek a mappák nem írhatóak számára, nem fognak sikeresen
lefutni. Akkor jó, ha ehhez hasonló üzeneteket találsz a konzolban (nem
feltétlenül közvetlenül egymás után):

```
takehome-db          | [INFO] /docker-entrypoint-initdb.d/tx_o_Budapest_19012023.csv adatok importálása sikeres
takehome-db          | 2026-03-06T13:31:41.803028Z 0 [System] [MY-010931] [Server] /usr/sbin/mysqld: ready for connections.
takehome-client-dev  |   VITE v7.3.1  ready in 149 ms
takehome-client-dev  |
takehome-client-dev  |   ➜  Local:   http://localhost:5173/
takehome-api-dev     | 2026-03-06 13:31:45,850 INFO exited: composer-install (exit status 0; expected)
```

Az `api-dev` container működése ellenőrizhető az alábbi paranccsal:

```sh
$ curl localhost:8080/hello?name=vendég

{"message":"Hello, vendég!"}
```

A `client-dev` containeré pedig ezzel:

```sh
$ curl localhost:5173

<!doctype html>
<html lang="hu">

<head>
# stb.
```

Ha ezen a ponton nem találod a fentebb említett üzeneteket, az ellenőrző
parancsokra nem ezeket a válaszokat kapod, vagy más hibát tapasztalsz, azt
jelezd felénk! [hiring@adiumsoft.hu](mailto:hiring@adiumsoft.hu)

A mintaadatokat egy MySQL adatbázisban tároljuk, amely a host gépről a `8081`
porton érhető el. A használatához szükséges felhasználónév `root`, a jelszó
szintén `root`. Természetesen a containeren belülről is használható:

```sh
docker compose exec db sh -c 'mysql --user=root --password=root'
```

A forráskódban változó-, függvény-, függvényparaméter- és mezőnevek esetén
camelCase írásmódot használj. Osztályok, típusok, React komponenesek nevei
legyenek PascalCase módon formázva.

### Szerveroldal

A szerveroldalon PHP-t használj, vedd igénybe az előre elkészített
[keretrendszer komponenseket](./api/src/Framework) a HTTP kérések kezeléséhez és az
adatbázis-lekérdezésekhez. Készítsd el az alábbi két végpontot:

1. Query paraméterként város azonosítót, kezdő- és végdátumot fogad és
   visszaadja a város időjárási adatait. 

2. Query paraméterként _régiómegnevezést_ (pl. Dunántúl), kezdő- és végdátumot
   fogad és visszaadja a megadott régió városainak _átlagolt_ időjárási adatait.

Az adatokból (kliensoldalon) olyan táblázatot kell készíteni, amelyben
feltünteted a minimum-, maximum- és átlaghőmérsékletet, valamint a csapadék
adatokat, valamennyi esetben mértékegységgel együtt. A táblázat láblécében fel
kell tüntetned az egyes oszlopok medián- és módusz értékét. 

A végpontok, query paraméterek neve és a visszaadott adatok struktúrája szabadon
megválasztható, a cél az adatok megjelenítése. A végpontok válasza JSON
formátumú szöveg legyen. 

A fent említett végpontokon kívül továbbiak és létrehozhatóak, amennyiben
szükségesnek ítéled.

### Kliensoldal

Kliensoldalon a UI létrehozásához használd a React könyvtárat, a TypeScript
nyelvet és a Vite build rendszert. Hozz létre két felületet:

1. Egy várost, illetve egy kezdő- és végdátumot lehet rajta kiválasztani. Egy
   gomb megnyomására letölti a város adott időszakbeli időjárás adatait és
   táblázatos formában jeleníti meg. A táblázat oszlopai legyenek a minimum-,
   maximum- és átlaghőmérséklet, valamint a csapadék adatok. Az értékek mellett
   a megfelelő mértékegységet is tüntesd fel.

1. Egy _régiót_, illetve egy kezdő- és végdátumot lehet rajta kiválasztani. Egy
   gomb megnyomására letölti a régió adott időszakbeli _átlagolt_ időjárás adatait és
   táblázatos formában jeleníti meg. A táblázat oszlopai legyenek a minimum-,
   maximum- és átlaghőmérséklet, valamint a csapadék adatok. Az értékek mellett
   a megfelelő mértékegységet is tüntesd fel.

Az átlaghőmérsékletet a minimum- és maximum hőmérséklet adatokból számold ki! Az
oldalak elérési módja tetszőleges - használhatsz bármilyen routing könyvtárat
(pl. [TanStack Router](https://tanstack.com/router/latest)), de anélkül is
tökéletesen megfelelő. A felületek formázása szintén tetszőleges, de ne a
böngésző által biztosított alapértelmezett kinézet legyen. Lehetőség szerint
használd a Tailwind CSS keretrendszert a formázáshoz (de nyers CSS is jó).

### Extra feladatok

Ezek a feladatok nem kötelezőek, de rajtuk keresztül be tudod mutatni az egyes
technológiák mélyebb ismeretét.

1. A felhasználói felületen lehessen váltani SI és angolszász mértékegységek
   (Celsius helyett Fahrenheit, mm helyett inch) között!

1. Írj automata teszteket a szerveroldali működéshez PHPUnit segítségével!

1. Vizsgáld meg az adatbázis felépítését és tartalmát. Fogalmazz meg
   javaslatokat a hibásnak, javítandónak vélt részekkel kapcsolatban és írd le
   őket a `db-suggestions.md` fájlba.

1. Használd a PHP forráskód formázó és statikus elemző parancsokat (`composer
   format`, `composer analyse`). Javítsd ki az esetleges statikus elemző által
   jelzett hibákat!

---

## Megvalósítás és Mérnöki Döntések (Engineering Decisions & Trade-offs)

Az alábbiakban összefoglalom a megoldás során hozott architektúrális és tesztelési döntéseimet, indoklásaimat, valamint a megvalósított extra funkciókat:

### 1. Backend & Adatbázis Architektúra
* **Keretrendszer bővítése:** A meglévő vékony keretrendszert a feladat szellemében használtam fel. A `QueryBuilder` osztályt felkészítettem a több táblás `INNER JOIN` és aggregált lekérdezések tiszta, folyékony (fluent) kezelésére.
* **N+1 lekérdezések kiküszöbölése:** Mind a városi, mind a régiós lekérdezéseket egyetlen, optimalizált SQL utasítással futtatom le, így a szerver nem terheli felesleges körökkel az adatbázist.
* **Egységes SQL kifejezések (`WeatherExpressions`):** Az aggregációk (`AVG`, `MIN`, `MAX`, `SUM`) és a kerekítések (`ROUND(..., 2)`) logikáját külön komponensbe szerveztem ki, elkerülve a kódduplikációt.
* **HTTP & Hibakezelés:** A Routert kiegészítettem 405 Method Not Allowed kezeléssel, `Cache-Control` fejlécekkel és biztonsági beállításokkal (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`). Production környezetben a rendszer szivárgásmentes, maszkolt 500-as hibaüzenetet ad vissza.
* **OpenAPI 3.0 & Swagger UI:** A végpontokat teljes körűen dokumentáltam az `api/src/openapi.json` sémában, amely a böngészőből közvetlenül elérhető és tesztelhető a `/docs` végponton keresztül.

### 2. Frontend & Felhasználói Élmény (UX / UI / WCAG AA)
* **Modern Stack:** React 19 + TypeScript + Vite + Tailwind CSS v4 alapokon építettem fel a felületet.
* **Kétirányú SI ↔ Angolszász mértékegység váltás:** A felhasználó azonnal válthat Celsius (°C) / mm és Fahrenheit (°F) / inch között, anélkül, hogy a backendet újra le kellene kérdezni.
* **Reszponzivitás és Akadálymentesítés (WCAG 2.1 AA):**
  - A felület mobileszközökön (320px-től) és nagyméretű kijelzőkön is ergonomikusan használható.
  - A komponensek (Select, DatePicker, Button, Table) teljes körű ARIA támogatással (`aria-expanded`, `role="listbox"`, `aria-label`, billentyűzetes fókuszkeretek `focus-visible:ring-3`) és szigorú kontrasztarányokkal rendelkeznek a gyengénlátók támogatására.
* **Nagy adathalmazok kezelése:** A táblázat infinite scroll-t és rögzített fejlécet/láblécet (`sticky thead` és `tfoot`) kapott, a láblécben pedig valós időben számított medián és módusz értékek jelennek meg.

### 3. Tesztelési Stratégia és Trade-offok

A minőségbiztosítás során a legmagasabb megtérülésű (high-ROI), kritikus rétegekre fókuszáltam:

* **Backend Tesztek (PHPUnit 10 - 85 teszt / 306 assertion):**
  - **Unit tesztek (64 teszt):** QueryBuilder, WeatherQuery validáció (szökőnap feb. 29, érvénytelen dátumok, hiányzó paraméterek), WeatherExpressions, JsonHttpResponse, Enums.
  - **Adatbázis integrációs tesztek (6 teszt):** Valós MySQL kapcsolaton keresztül ellenőrzik a városi és régiós aggregációkat és a dátumtartomány-szűrést.
  - **E2E és Edge Case HTTP tesztek (15 teszt):** Valós HTTP hívásokkal tesztelik a 200 OK, 400 Bad Request, 404 Not Found, 405 Method Not Allowed állapotokat, URL kódolásokat és a production hibaelrejtést.
  - **Zero-Dependency HTTP kliens:** Külső Guzzle csomag behúzása helyett egy saját, beépített PHP `curl` alapú `CurlHttpClient` osztályt írtam az E2E tesztekhez. Ezzel elkerültem az Alpine Linux alatti felesleges csomagfüggőségeket és minimalizáltam a Docker image méretét.
* **Statikus Analízis & Kódminőség:**
  - `composer analyse` (PHPStan Level 6): 33 fájl, 0 hiba.
  - `composer format` (PHP-CS-Fixer): szigorú PSR-12 és Symfony kódformázási szabályok érvényesítése.
  - `npm run build`: szigorú TypeScript típusellenőrzés, 0 fordítási figyelmeztetés vagy hiba.
* **Teljesítmény- és Terheléstesztek:**
  - k6 terhelésteszt forgatókönyv (`api/test/stress/weather-load.js`) és wrk script (`api/test/stress/run-wrk.sh`) készült az API kapacitásának mérésére (SLA cél: p95 < 250ms).
* **Miért nem készítettem külön Vitest/Jest frontend komponens teszteket? (Trade-off):**
  - Egy egyoldalas, dedikált vizualizációs dashboardnál az üzleti kockázatok 90%-a a backend adatfeldolgozásban, az SQL aggregációkban és a kliens–szerver adatkommunikációban rejlik.
  - A szigorú TypeScript típusellenőrzés, az OpenAPI szerződéses séma és az élő E2E HTTP tesztek kombinációja elegendő és stabil védelmet nyújt a törések ellen. Éles vállalati környezetben a következő lépés a Vitest + React Testing Library bevezetése lenne az izolált űrlapállapotok unit-szintű vizsgálatára.

### 4. Adatbázis Elemzés és Valós Benchmarking
* A `db-suggestions.md` dokumentumban részletesen feltártam a forrásadatbázis strukturális problémáit (hiányzó PK/FK, indexálatlanság, memóriapazarló adattípusok, 10-es város hibája: Szombathely duplikáció Túrkeve helyett).
* Létrehoztam a refaktorált, modernizált `weather_optimized` MySQL adatbázist és a gyökérben elérhetővé tettem a teljes dumpot ([`weather_optimized.sql`](./weather_optimized.sql)).
* **Mért eredmény:** A denormalizált mérési tábla és az összetett indexek révén az 1 éves városi lekérdezés futási idejét **1029 ms-ról 0.26 ms-ra csökkentettem (~3900× gyorsulás)**, miközben az adatbázis lemezterületét **43.6%-kal csökkentettem**.

