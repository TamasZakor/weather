# Terhelés- és Teljesítménytesztek (k6 & wrk)

Ez a könyvtár a meteorológiai API nagy terhelés alatti viselkedését vizsgáló scripteket tartalmazza.

---

## 1. k6 Forgatókönyv-alapú Terhelésteszt (`weather-load.js`)

A `weather-load.js` életszerű felhasználói viselkedést szimulál:
- Metaadatok lekérése (`/api/meta`)
- Városi időjárás szűrése 1 évre (`/api/weather/city`)
- Régiós aggregáció lekérése (`/api/weather/region`)
- **SLA Thresholdok:** 95%-os válaszidő < 250ms, hibaarány < 1%.

### Futtatás k6-tal:

#### A) Helyi gépre telepített k6-tal:
```bash
# Telepítés után (pl. winget install grafana.k6 vagy brew install k6):
k6 run api/test/stress/weather-load.js

# Egyedi cél URL megadásával:
k6 run -e BASE_URL=http://localhost:8300 api/test/stress/weather-load.js
```

#### B) Docker segítségével (nem szükséges helyi k6 telepítés):
```bash
docker run --rm -i --network=ads_takehome \
  -e BASE_URL=http://api-dev \
  grafana/k6 run - < api/test/stress/weather-load.js
```

---

## 2. wrk Áteresztőképesség (Throughput & Latency) Mérés

A `wrk` egy ultra-gyors, C-alapú benchmark eszköz, amely minimális CPU overhead mellett méri a szerver maximális áteresztőképességét (Requests Per Second) és a p50, p75, p90, p99 késleltetéseket.

### Futtatás:

#### A) Shell scripttel:
```bash
chmod +x api/test/stress/run-wrk.sh
./api/test/stress/run-wrk.sh http://localhost:8080
```

#### B) Közvetlen parancsokkal:
```bash
# 2 szál, 50 konkurens kapcsolat, 10 másodpercig:
wrk -t2 -c50 -d10s --latency http://localhost:8080/api/meta
```

#### C) Docker segítségével:
```bash
docker run --rm --network=ads_takehome \
  williamyeh/wrk -t2 -c30 -d10s --latency http://api-dev/api/meta
```
