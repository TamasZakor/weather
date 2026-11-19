import { useEffect, useRef, useState } from "react";

export interface SelectOption {
  value: string | number;
  label: string;
}

interface SelectProps {
  label: string;
  value: string | number;
  onChange: (value: string) => void;
  options: SelectOption[];
  id?: string;
  disabled?: boolean;
}

/**
 * Prémium, egyedi dizájnú legördülő választó (Custom Dropdown):
 * - Lekerekített lebegő menü (rounded-2xl, shadow-xl)
 * - Légies égkék kijelölési színek, tökéletes összhang a meteorológiai dizájnnal
 * - Klikk-kívülre zárás és akadálymentes kezelés
 */
export function Select({
  label,
  value,
  onChange,
  options,
  disabled = false,
}: SelectProps) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const selectedOption = options.find((opt) => String(opt.value) === String(value)) || options[0];

  // Kattintás kívülre -> menü bezárása
  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div className="w-full relative" ref={containerRef}>
      <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
        {label}
      </label>

      {/* Kattintható fő mező */}
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
        <span className="truncate">{selectedOption?.label || "Válassz..."}</span>
        <div className={`transition-transform duration-200 text-sky-500 ${isOpen ? "rotate-180" : ""}`}>
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </button>

      {/* Egyedi Lebegő Dropdown Lista */}
      {isOpen && (
        <div className="absolute z-50 left-0 right-0 mt-2 p-1.5 bg-white/95 backdrop-blur-xl border border-sky-100 rounded-2xl shadow-xl shadow-sky-950/10 max-h-64 overflow-y-auto divide-y divide-slate-50 animate-in fade-in zoom-in-95 duration-150">
          {options.map((option) => {
            const isSelected = String(option.value) === String(value);
            return (
              <div
                key={option.value}
                onClick={() => {
                  onChange(String(option.value));
                  setIsOpen(false);
                }}
                className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer ${
                  isSelected
                    ? "bg-linear-to-r from-sky-500 to-blue-600 text-white font-semibold shadow-sm"
                    : "text-slate-700 hover:bg-sky-50/80 hover:text-sky-950"
                }`}
              >
                <span>{option.label}</span>
                {isSelected && (
                  <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
