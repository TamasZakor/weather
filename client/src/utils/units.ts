/**
 * Mértékegység-rendszerek:
 * - metric: SI rendszer (°C, mm)
 * - imperial: Angolszász rendszer (°F, inch)
 */
export type UnitSystem = "metric" | "imperial";

/**
 * Hőmérséklet átszámítása és formázása.
 * Képlet: (°C × 9/5) + 32 = °F
 */
export function formatTemperature(celsius: number | null, unit: UnitSystem): string {
  if (celsius === null || isNaN(celsius)) {
    return "-";
  }

  if (unit === "imperial") {
    const fahrenheit = (celsius * 9) / 5 + 32;
    return `${fahrenheit.toFixed(1)} °F`;
  }

  return `${celsius.toFixed(1)} °C`;
}

/**
 * Csapadékmennyiség átszámítása és formázása.
 * Képlet: 1 mm = 1 / 25.4 inch (~0.03937 in)
 */
export function formatPrecipitation(mm: number | null, unit: UnitSystem): string {
  if (mm === null || isNaN(mm)) {
    return "-";
  }

  if (unit === "imperial") {
    const inches = mm / 25.4;
    return `${inches.toFixed(2)} in`;
  }

  return `${mm.toFixed(1)} mm`;
}

/**
 * Nyers numerikus érték konverziója (statisztikákhoz, pl. módusz / medián számítás).
 */
export function convertTemperatureValue(celsius: number, unit: UnitSystem): number {
  if (unit === "imperial") {
    return Number(((celsius * 9) / 5 + 32).toFixed(1));
  }
  return Number(celsius.toFixed(1));
}

export function convertPrecipitationValue(mm: number, unit: UnitSystem): number {
  if (unit === "imperial") {
    return Number((mm / 25.4).toFixed(2));
  }
  return Number(mm.toFixed(1));
}
