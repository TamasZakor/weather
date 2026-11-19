/**
 * Statisztikai számítások: Medián és Módusz
 */

/**
 * Kiszámolja a kapott számtömb mediánját (középső értékét).
 * Páros darabszám esetén a két középső elem számtani átlagát adja vissza.
 */
export function calculateMedian(numbers: number[]): number | null {
  if (numbers.length === 0) {
    return null;
  }

  // Másolat készítése és növekvő sorrendbe rendezés
  const sorted = [...numbers].sort((a, b) => a - b);
  const midIndex = Math.floor(sorted.length / 2);

  if (sorted.length % 2 !== 0) {
    // Páratlan: a pontos középső elem
    return Number(sorted[midIndex].toFixed(2));
  }

  // Páros: a két középső elem átlaga
  const median = (sorted[midIndex - 1] + sorted[midIndex]) / 2;
  return Number(median.toFixed(2));
}

/**
 * Kiszámolja a kapott számtömb móduszát (leggyakrabban előforduló értékét).
 * Ha több ilyen is van, a legkisebbet / legelsőt adja vissza.
 */
export function calculateMode(numbers: number[]): number | null {
  if (numbers.length === 0) {
    return null;
  }

  const frequencyMap = new Map<number, number>();
  let maxFrequency = 0;
  let modeValue = numbers[0];

  for (const num of numbers) {
    // 2 tizedesjegyre kerekítünk a lebegőpontos azonosítás miatt
    const rounded = Number(num.toFixed(2));
    const count = (frequencyMap.get(rounded) ?? 0) + 1;
    frequencyMap.set(rounded, count);

    if (count > maxFrequency) {
      maxFrequency = count;
      modeValue = rounded;
    }
  }

  return modeValue;
}
