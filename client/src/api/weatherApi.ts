import type { DailyWeatherRecord, MetadataResponse } from "../types/weather";

const API_BASE_URL = import.meta.env.VITE_API_URL || "";

/**
 * In-memory gyorsítótár (Cache), hogy a statikus adatok ne generáljanak
 * felesleges hálózati kéréseket.
 */
const cache = new Map<string, unknown>();

/**
 * Városok és régiók listájának lekérése gyorsítótárral.
 */
export async function fetchMetadata(): Promise<MetadataResponse> {
  const cacheKey = "metadata";
  if (cache.has(cacheKey)) {
    return cache.get(cacheKey) as MetadataResponse;
  }

  const response = await fetch(`${API_BASE_URL}/api/meta`);
  if (!response.ok) {
    const errorBody = await response.json().catch(() => ({}));
    throw new Error(errorBody.error || `Hiba a metaadatok lekérésekor (${response.status})`);
  }

  const data: MetadataResponse = await response.json();
  cache.set(cacheKey, data);
  return data;
}

/**
 * Adott város időjárási adatainak lekérése a megadott időszakra.
 */
export async function fetchCityWeather(
  cityId: number,
  startDate: string,
  endDate: string,
): Promise<DailyWeatherRecord[]> {
  const cacheKey = `city-${cityId}-${startDate}-${endDate}`;
  if (cache.has(cacheKey)) {
    return cache.get(cacheKey) as DailyWeatherRecord[];
  }

  const params = new URLSearchParams({
    cityId: String(cityId),
    startDate,
    endDate,
  });

  const response = await fetch(`${API_BASE_URL}/api/weather/city?${params.toString()}`);
  if (!response.ok) {
    const errorBody = await response.json().catch(() => ({}));
    throw new Error(errorBody.error || `Hiba a város adatainak lekérésekor (${response.status})`);
  }

  const data: DailyWeatherRecord[] = await response.json();
  cache.set(cacheKey, data);
  return data;
}

/**
 * Adott régió átlagolt adatainak lekérése a megadott időszakra.
 */
export async function fetchRegionWeather(
  region: string,
  startDate: string,
  endDate: string,
): Promise<DailyWeatherRecord[]> {
  const cacheKey = `region-${region}-${startDate}-${endDate}`;
  if (cache.has(cacheKey)) {
    return cache.get(cacheKey) as DailyWeatherRecord[];
  }

  const params = new URLSearchParams({
    region,
    startDate,
    endDate,
  });

  const response = await fetch(`${API_BASE_URL}/api/weather/region?${params.toString()}`);
  if (!response.ok) {
    const errorBody = await response.json().catch(() => ({}));
    throw new Error(errorBody.error || `Hiba a régió adatainak lekérésekor (${response.status})`);
  }

  const data: DailyWeatherRecord[] = await response.json();
  cache.set(cacheKey, data);
  return data;
}
