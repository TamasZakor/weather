import { useEffect, useMemo, useRef, useState } from "react";
import i18n from "../i18n/hu.json";
import type { DailyWeatherRecord } from "../types/weather";
import { calculateMedian, calculateMode } from "../utils/statistics";
import { formatPrecipitation, formatTemperature, type UnitSystem } from "../utils/units";

interface WeatherTableProps {
  records: DailyWeatherRecord[];
  unitSystem?: UnitSystem;
}

const INITIAL_BATCH_SIZE = 50;
const BATCH_INCREMENT = 50;

/**
 * Időjárási adatokat megjelenítő táblázat:
 * - Fix (sticky) fejléc és összesítő lábléc,
 * - Belső görgetősáv (max-height) több ezer sor kényelmes böngészéséhez,
 * - Infinite scroll / virtuális betöltő (batch chunking) a villámgyors DOM rendereléshez,
 * - SI (°C, mm) és Angolszász (°F, in) mértékegységek támogatása,
 * - Medián és módusz számítás a teljes lekérdezett adathalmazra.
 */
export function WeatherTable({ records, unitSystem = "metric" }: WeatherTableProps) {
  const [visibleCount, setVisibleCount] = useState<number>(INITIAL_BATCH_SIZE);
  const containerRef = useRef<HTMLDivElement>(null);

  // Új lekérdezés esetén visszaállítjuk az első adag megjelenítésére
  useEffect(() => {
    setVisibleCount(INITIAL_BATCH_SIZE);
    if (containerRef.current) {
      containerRef.current.scrollTop = 0;
    }
  }, [records]);

  // Statisztikák számítása a TELJES lekérdezett adathalmazra (alapértelmezett Celsius/mm adatokból)
  const stats = useMemo(() => {
    if (records.length === 0) return null;
    const minTemps = records.map((r) => r.minTemp);
    const maxTemps = records.map((r) => r.maxTemp);
    const avgTemps = records.map((r) => r.avgTemp);
    const precipitations = records.map((r) => r.precipitation);

    return {
      minTemp: { median: calculateMedian(minTemps), mode: calculateMode(minTemps) },
      maxTemp: { median: calculateMedian(maxTemps), mode: calculateMode(maxTemps) },
      avgTemp: { median: calculateMedian(avgTemps), mode: calculateMode(avgTemps) },
      precipitation: { median: calculateMedian(precipitations), mode: calculateMode(precipitations) },
    };
  }, [records]);

  // Infinite scroll eseménykezelő: ha a belső görgetősáv az aljához közelít
  const handleScroll = () => {
    const el = containerRef.current;
    if (!el) return;

    // Ha kevesebb mint 150px van hátra az aljáig és van még több sor
    if (el.scrollHeight - el.scrollTop - el.clientHeight < 150) {
      setVisibleCount((prev) => Math.min(prev + BATCH_INCREMENT, records.length));
    }
  };

  if (records.length === 0) {
    return (
      <div className="p-12 text-center bg-white/70 backdrop-blur-sm rounded-3xl border border-dashed border-sky-200 text-slate-500 shadow-sm flex flex-col items-center justify-center gap-2">
        <svg className="w-10 h-10 text-sky-400/80 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z" />
        </svg>
        <p className="font-semibold text-slate-700">{i18n.table.noData}</p>
        <p className="text-xs text-slate-400">{i18n.table.noDataHint}</p>
      </div>
    );
  }

  const visibleRecords = records.slice(0, visibleCount);
  const tempUnitLabel = unitSystem === "imperial" ? "°F" : "°C";
  const precipUnitLabel = unitSystem === "imperial" ? "in" : "mm";

  return (
    <div className="overflow-hidden rounded-3xl border border-sky-100 shadow-md shadow-sky-500/5 bg-white">
      {/* Információs sáv sok sor esetén */}
      <div className="px-6 py-2.5 bg-slate-50/80 border-b border-sky-100 text-xs font-semibold text-slate-500 flex justify-between items-center">
        <span>
          {i18n.table.displayed} <strong className="text-sky-700">{visibleRecords.length}</strong> / {records.length} nap
        </span>
        {records.length > visibleRecords.length && (
          <span className="text-sky-600 animate-pulse">{i18n.table.scrollHint}</span>
        )}
      </div>

      {/* Görgethető táblázat konténer fix magassággal (max-h-140) */}
      <div
        ref={containerRef}
        onScroll={handleScroll}
        className="overflow-x-auto max-h-140 overflow-y-auto scroll-smooth"
      >
        <table className="w-full text-left border-collapse text-sm">
          {/* Sticky (ragadós) fejléc */}
          <thead className="sticky top-0 z-10 bg-linear-to-r from-sky-50 to-blue-50 text-sky-950 font-bold shadow-xs">
            <tr className="border-b border-sky-100">
              <th className="py-3.5 px-5 bg-sky-50/95 backdrop-blur-xs">{i18n.table.date}</th>
              <th className="py-3.5 px-5 text-right bg-sky-50/95 backdrop-blur-xs">
                Min. ({tempUnitLabel})
              </th>
              <th className="py-3.5 px-5 text-right bg-sky-50/95 backdrop-blur-xs">
                Max. ({tempUnitLabel})
              </th>
              <th className="py-3.5 px-5 text-right bg-sky-50/95 backdrop-blur-xs">
                Átlag ({tempUnitLabel})
              </th>
              <th className="py-3.5 px-5 text-right bg-sky-50/95 backdrop-blur-xs">
                Csapadék ({precipUnitLabel})
              </th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100 text-slate-700">
            {visibleRecords.map((row) => (
              <tr key={row.date} className="hover:bg-sky-50/40 transition-colors">
                <td className="py-3 px-5 font-mono font-medium text-slate-900">{row.date}</td>
                <td className="py-3 px-5 text-right font-mono text-cyan-800">
                  {formatTemperature(row.minTemp, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-amber-700">
                  {formatTemperature(row.maxTemp, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono font-semibold text-blue-700">
                  <span className="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-lg border border-blue-100/60">
                    {formatTemperature(row.avgTemp, unitSystem)}
                  </span>
                </td>
                <td className="py-3 px-5 text-right font-mono text-sky-700 font-medium">
                  {formatPrecipitation(row.precipitation, unitSystem)}
                </td>
              </tr>
            ))}
          </tbody>
          {/* Sticky (ragadós) lábléc az összesítésekkel */}
          {stats && (
            <tfoot className="sticky bottom-0 z-10 bg-slate-50/95 backdrop-blur-xs font-medium text-slate-800 border-t-2 border-sky-100 shadow-sm">
              {/* Medián sor */}
              <tr className="border-b border-slate-200/70">
                <td className="py-3 px-5 font-bold text-slate-900 flex items-center gap-2">
                  <span className="w-1.5 h-4 bg-sky-500 rounded-full"></span>
                  {i18n.table.median}
                </td>
                <td className="py-3 px-5 text-right font-mono text-cyan-900 font-semibold">
                  {formatTemperature(stats.minTemp.median, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-amber-900 font-semibold">
                  {formatTemperature(stats.maxTemp.median, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-blue-800 font-bold">
                  {formatTemperature(stats.avgTemp.median, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-sky-800 font-semibold">
                  {formatPrecipitation(stats.precipitation.median, unitSystem)}
                </td>
              </tr>
              {/* Módusz sor */}
              <tr>
                <td className="py-3 px-5 font-bold text-slate-900 flex items-center gap-2">
                  <span className="w-1.5 h-4 bg-blue-600 rounded-full"></span>
                  {i18n.table.mode}
                </td>
                <td className="py-3 px-5 text-right font-mono text-cyan-900 font-semibold">
                  {formatTemperature(stats.minTemp.mode, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-amber-900 font-semibold">
                  {formatTemperature(stats.maxTemp.mode, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-blue-800 font-bold">
                  {formatTemperature(stats.avgTemp.mode, unitSystem)}
                </td>
                <td className="py-3 px-5 text-right font-mono text-sky-800 font-semibold">
                  {formatPrecipitation(stats.precipitation.mode, unitSystem)}
                </td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>
    </div>
  );
}
