import { useEffect, useState } from "react";
import { fetchCityWeather, fetchMetadata, fetchRegionWeather } from "./api/weatherApi";
import { Button } from "./components/ui/Button";
import { DatePicker } from "./components/ui/DatePicker";
import { Select } from "./components/ui/Select";
import { WeatherTable } from "./components/WeatherTable";
import i18n from "./i18n/hu.json";
import type { City, DailyWeatherRecord } from "./types/weather";
import type { UnitSystem } from "./utils/units";

type ViewMode = "city" | "region";

export function App() {
  const [viewMode, setViewMode] = useState<ViewMode>("city");
  const [unitSystem, setUnitSystem] = useState<UnitSystem>("metric");

  // Metaadatok (választók feltöltéséhez)
  const [cities, setCities] = useState<City[]>([]);
  const [regions, setRegions] = useState<string[]>([]);
  const [dateRange, setDateRange] = useState<{ minDate: string; maxDate: string }>({
    minDate: "1901-01-01",
    maxDate: "2023-12-31",
  });

  // Kiválasztott szűrők
  const [selectedCityId, setSelectedCityId] = useState<number>(1);
  const [selectedRegion, setSelectedRegion] = useState<string>("Dunántúl");
  const [startDate, setStartDate] = useState<string>("1901-01-01");
  const [endDate, setEndDate] = useState<string>("1901-01-31");

  // Eredmények és állapotok
  const [records, setRecords] = useState<DailyWeatherRecord[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  // Metaadatok letöltése az indításkor
  useEffect(() => {
    async function loadMeta() {
      try {
        const meta = await fetchMetadata();
        setCities(meta.cities);
        setRegions(meta.regions);
        if (meta.dateRange) {
          setDateRange(meta.dateRange);
          // Ha szeretnénk a kezdő dátumot dinamikusan a DB legkorábbijához igazítani
          if (!startDate) {
            setStartDate(meta.dateRange.minDate);
          }
        }
        if (meta.cities.length > 0) {
          setSelectedCityId(meta.cities[0].id);
        }
        if (meta.regions.length > 0) {
          setSelectedRegion(meta.regions[0]);
        }
      } catch (e) {
        setError(`${i18n.errors.metadataFailed}: ${e instanceof Error ? e.message : String(e)}`);
      }
    }
    loadMeta();
  }, []);

  // Adatok lekérése a kiválasztott szűrők szerint
  const handleQuery = async () => {
    if (!startDate || !endDate) {
      setError(i18n.errors.requiredDates);
      return;
    }

    if (startDate > endDate) {
      setError(i18n.errors.invalidDateRange);
      return;
    }

    setError(null);
    setIsLoading(true);

    try {
      let data: DailyWeatherRecord[] = [];
      if (viewMode === "city") {
        data = await fetchCityWeather(selectedCityId, startDate, endDate);
      } else {
        data = await fetchRegionWeather(selectedRegion, startDate, endDate);
      }
      setRecords(data);
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
      setRecords([]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-linear-to-br from-slate-50 via-sky-50/40 to-blue-50/30 py-10 px-4 sm:px-6 lg:px-8 font-sans">
      <div className="max-w-5xl mx-auto space-y-6">
        {/* Fejléc Időjárás Hangulattal */}
        <header className="bg-white/80 backdrop-blur-md p-6 sm:p-8 rounded-3xl shadow-sm border border-sky-100/80">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-100/70 text-sky-800 text-xs font-semibold mb-2">
                <span className="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                Történelmi meteorológiai archívum (1901–2023)
              </div>
              <h1 className="text-3xl font-extrabold text-slate-800 tracking-tight">
                {i18n.app.title}
              </h1>
              <p className="text-slate-500 text-sm mt-1">{i18n.app.description}</p>
            </div>

            {/* Nézetváltó és Mértékegység Kapcsolók */}
            <div className="flex flex-wrap sm:flex-nowrap items-center gap-3">
              {/* Nézetváltó Tab gombok */}
              <div className="flex gap-1.5 p-1.5 bg-slate-100/80 rounded-2xl border border-slate-200/60 w-fit">
                <Button
                  variant="tab"
                  isActive={viewMode === "city"}
                  onClick={() => {
                    setViewMode("city");
                    setRecords([]);
                    setError(null);
                  }}
                >
                  {i18n.actions.cityView}
                </Button>
                <Button
                  variant="tab"
                  isActive={viewMode === "region"}
                  onClick={() => {
                    setViewMode("region");
                    setRecords([]);
                    setError(null);
                  }}
                >
                  {i18n.actions.regionView}
                </Button>
              </div>

              {/* Mértékegység Váltó Kapcsoló (SI / Angolszász) 2 soros elegáns elrendezéssel */}
              <div className="flex gap-1.5 p-1.5 bg-sky-100/60 rounded-2xl border border-sky-200/60 w-fit">
                <button
                  type="button"
                  onClick={() => setUnitSystem("metric")}
                  className={`px-3.5 py-1.5 text-center rounded-xl transition-all cursor-pointer flex flex-col items-center justify-center ${
                    unitSystem === "metric"
                      ? "bg-white text-sky-700 shadow-xs border border-sky-200/70"
                      : "text-slate-600 hover:text-slate-900"
                  }`}
                >
                  <span className="text-xs font-bold leading-tight">{i18n.units.metricTitle}</span>
                  <span className="text-[10px] font-semibold opacity-75 leading-tight whitespace-nowrap">{i18n.units.metricSub}</span>
                </button>
                <button
                  type="button"
                  onClick={() => setUnitSystem("imperial")}
                  className={`px-3.5 py-1.5 text-center rounded-xl transition-all cursor-pointer flex flex-col items-center justify-center ${
                    unitSystem === "imperial"
                      ? "bg-white text-sky-700 shadow-xs border border-sky-200/70"
                      : "text-slate-600 hover:text-slate-900"
                  }`}
                >
                  <span className="text-xs font-bold leading-tight">{i18n.units.imperialTitle}</span>
                  <span className="text-[10px] font-semibold opacity-75 leading-tight whitespace-nowrap">{i18n.units.imperialSub}</span>
                </button>
              </div>
            </div>
          </div>
        </header>

        {/* Szűrő Panel tiszta újrafelhasználható komponensekkel */}
        <div className="relative z-30 bg-white/80 backdrop-blur-md p-6 sm:p-7 rounded-3xl shadow-sm border border-sky-100/80">
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end">
            {/* Város vagy Régió választó */}
            {viewMode === "city" ? (
              <Select
                label={i18n.fields.city}
                value={selectedCityId}
                onChange={(val) => setSelectedCityId(Number(val))}
                options={cities.map((city) => ({
                  value: city.id,
                  label: `${city.name} (${city.region})`,
                }))}
              />
            ) : (
              <Select
                label={i18n.fields.region}
                value={selectedRegion}
                onChange={setSelectedRegion}
                options={regions.map((region) => ({
                  value: region,
                  label: region,
                }))}
              />
            )}

            {/* Kezdő dátum */}
            <DatePicker
              label={i18n.fields.startDate}
              value={startDate}
              onChange={setStartDate}
              min={dateRange.minDate}
              max={dateRange.maxDate}
            />

            {/* Záró dátum */}
            <DatePicker
              label={i18n.fields.endDate}
              value={endDate}
              onChange={setEndDate}
              min={dateRange.minDate}
              max={dateRange.maxDate}
            />

            {/* Lekérdezés gomb */}
            <div>
              <Button onClick={handleQuery} isLoading={isLoading}>
                {isLoading ? i18n.actions.loading : i18n.actions.query}
              </Button>
            </div>
          </div>

          {/* Hibaüzenet */}
          {error && (
            <div className="mt-5 p-4 bg-red-50/80 border border-red-200/80 text-red-700 text-sm font-medium rounded-2xl flex items-center gap-3">
              <svg className="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{error}</span>
            </div>
          )}
        </div>

        {/* Eredmény táblázat */}
        <main>
          <WeatherTable records={records} unitSystem={unitSystem} />
        </main>
      </div>
    </div>
  );
}
