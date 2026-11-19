import http from 'k6/http';
import { check, sleep, group } from 'k6';

// k6 terhelésteszt konfiguráció és SLA elvárások
export const options = {
    stages: [
        { duration: '15s', target: 10 },  // 0 -> 10 felhasználó felfutás
        { duration: '30s', target: 30 },  // 30 egyidejű konkurens felhasználó terhelés
        { duration: '15s', target: 0 },   // fokozatos leállás
    ],
    thresholds: {
        // A kérések 95%-ának 250 ms alatt kell teljesülnie
        http_req_duration: ['p(95)<250'],
        // A hibás kérések aránya 1% alatt kell maradjon
        http_req_failed: ['rate<0.01'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export default function () {
    // 1. Metaadatok lekérdezése (tipikus induló hívás)
    group('01_Metadata', function () {
        const res = http.get(`${BASE_URL}/api/meta`);
        check(res, {
            'meta status is 200': (r) => r.status === 200,
            'has cities': (r) => JSON.parse(r.body).cities.length > 0,
            'cache header is present': (r) => r.headers['Cache-Control'] !== undefined,
        });
    });

    sleep(0.5);

    // 2. Városi időjárás lekérése (1 éves intervallum Budapest)
    group('02_CityWeather', function () {
        const res = http.get(`${BASE_URL}/api/weather/city?cityId=1&startDate=1950-01-01&endDate=1950-12-31`);
        check(res, {
            'city status is 200': (r) => r.status === 200,
            'returns 365 days': (r) => JSON.parse(r.body).length === 365,
        });
    });

    sleep(0.5);

    // 3. Régiós aggregált időjárás lekérése (Dunántúl régió)
    group('03_RegionWeather', function () {
        const res = http.get(`${BASE_URL}/api/weather/region?region=Dun%C3%A1nt%C3%BAl&startDate=1950-01-01&endDate=1950-12-31`);
        check(res, {
            'region status is 200': (r) => r.status === 200,
            'has aggregated records': (r) => JSON.parse(r.body).length > 0,
        });
    });

    sleep(1);
}
