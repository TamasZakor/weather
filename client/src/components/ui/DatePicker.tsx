import { useEffect, useRef, useState } from "react";
import i18n from "../../i18n/hu.json";

interface DatePickerProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
  min?: string;
  max?: string;
  id?: string;
  disabled?: boolean;
}

/**
 * Teljesen testreszabott, modern naptár felugró (Custom Calendar Dropdown):
 * - Lekerekített lebegő naptár (rounded-3xl, shadow-2xl), nem a szögletes natív böngészős nézet
 * - Meteorológiai égkék-kék dizájnnal megegyező színek
 * - Gyors év és hónap navigáció
 * - Minden szöveg i18n JSON-ból származik
 */
export function DatePicker({
  label,
  value,
  onChange,
  min = "1901-01-01",
  max = "2023-12-31",
  disabled = false,
}: DatePickerProps) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  // Értelmezzük az aktuális dátumot
  const parsedDate = value ? new Date(value) : new Date(1901, 0, 1);
  const [viewYear, setViewYear] = useState<number>(
    isNaN(parsedDate.getFullYear()) ? 1901 : parsedDate.getFullYear()
  );
  const [viewMonth, setViewMonth] = useState<number>(
    isNaN(parsedDate.getMonth()) ? 0 : parsedDate.getMonth()
  );

  // Ha a külső value megváltozik, szinkronizáljuk a nézetet
  useEffect(() => {
    if (value) {
      const d = new Date(value);
      if (!isNaN(d.getTime())) {
        setViewYear(d.getFullYear());
        setViewMonth(d.getMonth());
      }
    }
  }, [value]);

  // Klikk kívülre figyelő
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const monthNames = i18n.calendar.months;
  const dayNames = i18n.calendar.days;

  const minYear = parseInt(min.split("-")[0], 10);
  const maxYear = parseInt(max.split("-")[0], 10);

  // Hónap napjainak generálása
  const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
  const firstDayIndex = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7; // Hétfő = 0

  const handlePrevMonth = () => {
    if (viewMonth === 0) {
      if (viewYear > minYear) {
        setViewYear((y) => y - 1);
        setViewMonth(11);
      }
    } else {
      setViewMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    if (viewMonth === 11) {
      if (viewYear < maxYear) {
        setViewYear((y) => y + 1);
        setViewMonth(0);
      }
    } else {
      setViewMonth((m) => m + 1);
    }
  };

  const handleSelectDay = (day: number) => {
    const formattedMonth = String(viewMonth + 1).padStart(2, "0");
    const formattedDay = String(day).padStart(2, "0");
    const dateStr = `${viewYear}-${formattedMonth}-${formattedDay}`;

    if (dateStr >= min && dateStr <= max) {
      onChange(dateStr);
      setIsOpen(false);
    }
  };

  return (
    <div className="w-full relative" ref={containerRef}>
      <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
        {label}
      </label>

      {/* Kattintható beviteli mező */}
      <button
        type="button"
        disabled={disabled}
        onClick={() => setIsOpen((prev) => !prev)}
        className={`w-full flex items-center justify-between px-4 py-2.5 bg-white/95 hover:bg-white border rounded-2xl text-slate-800 text-sm font-semibold tracking-wide transition-all shadow-xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed ${
          isOpen
            ? "border-sky-500 ring-4 ring-sky-500/15"
            : "border-slate-200/90 hover:border-sky-300"
        }`}
      >
        <div className="flex items-center gap-2.5">
          <svg className="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <span className="font-mono text-slate-900">{value || i18n.calendar.placeholder}</span>
        </div>
        <span className="text-xs text-sky-600 font-medium">{i18n.calendar.select}</span>
      </button>

      {/* Egyedi Lebegő Naptár Doboz */}
      {isOpen && (
        <div className="absolute z-50 left-0 mt-2 w-72 sm:w-80 p-4 bg-white/95 backdrop-blur-xl border border-sky-100 rounded-3xl shadow-2xl shadow-sky-950/15 animate-in fade-in zoom-in-95 duration-150">
          {/* Naptár fejléc & Év/Hónap léptető */}
          <div className="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
            <button
              type="button"
              onClick={handlePrevMonth}
              className="p-1.5 rounded-xl hover:bg-sky-50 text-slate-600 hover:text-sky-700 transition-colors cursor-pointer"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
              </svg>
            </button>

            <div className="flex items-center gap-1.5">
              <span className="text-sm font-bold text-slate-800">{monthNames[viewMonth]}</span>
              {/* Év választó */}
              <select
                value={viewYear}
                onChange={(e) => setViewYear(Number(e.target.value))}
                className="text-sm font-bold text-sky-700 bg-sky-50/80 px-2 py-0.5 rounded-lg border-none focus:outline-none cursor-pointer"
              >
                {Array.from({ length: maxYear - minYear + 1 }, (_, i) => minYear + i).map((y) => (
                  <option key={y} value={y}>
                    {y}
                  </option>
                ))}
              </select>
            </div>

            <button
              type="button"
              onClick={handleNextMonth}
              className="p-1.5 rounded-xl hover:bg-sky-50 text-slate-600 hover:text-sky-700 transition-colors cursor-pointer"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>

          {/* Hét napjai fejléc */}
          <div className="grid grid-cols-7 gap-1 text-center mb-2">
            {dayNames.map((day) => (
              <span key={day} className="text-xs font-bold text-slate-400">
                {day}
              </span>
            ))}
          </div>

          {/* Napok négyzethálója */}
          <div className="grid grid-cols-7 gap-1 text-center">
            {/* Üres napok a hónap elején */}
            {Array.from({ length: firstDayIndex }).map((_, i) => (
              <div key={`empty-${i}`} className="h-8" />
            ))}

            {/* A hónap napjai */}
            {Array.from({ length: daysInMonth }, (_, i) => i + 1).map((day) => {
              const formattedMonth = String(viewMonth + 1).padStart(2, "0");
              const formattedDay = String(day).padStart(2, "0");
              const dateStr = `${viewYear}-${formattedMonth}-${formattedDay}`;
              const isSelected = dateStr === value;
              const isDisabled = dateStr < min || dateStr > max;

              return (
                <button
                  key={day}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => handleSelectDay(day)}
                  className={`h-8 w-8 mx-auto flex items-center justify-center text-xs font-semibold rounded-xl transition-all cursor-pointer ${
                    isSelected
                      ? "bg-linear-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/30 scale-105"
                      : isDisabled
                      ? "text-slate-300 cursor-not-allowed"
                      : "text-slate-700 hover:bg-sky-50 hover:text-sky-700 active:scale-95"
                  }`}
                >
                  {day}
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
