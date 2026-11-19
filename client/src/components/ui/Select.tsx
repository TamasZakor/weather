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
  id,
  disabled = false,
}: SelectProps) {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);
  const selectId = id || `select-${label.toLowerCase().replace(/\s+/g, "-")}`;

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
      <label
        id={`${selectId}-label`}
        htmlFor={selectId}
        className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5"
      >
        {label}
      </label>

      {/* Kattintható fő mező ARIA attribútumokkal és éles fókuszkerettel */}
      <button
        id={selectId}
        type="button"
        disabled={disabled}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-labelledby={`${selectId}-label ${selectId}`}
        onClick={() => setIsOpen((prev) => !prev)}
        className={`w-full flex items-center justify-between px-4 py-2.5 bg-white/95 hover:bg-white border rounded-2xl text-slate-900 text-sm font-semibold tracking-wide transition-all shadow-xs cursor-pointer focus-visible:ring-3 focus-visible:ring-sky-600 focus-visible:outline-hidden disabled:opacity-50 disabled:cursor-not-allowed ${
          isOpen
            ? "border-sky-600 ring-4 ring-sky-500/20"
            : "border-slate-300 hover:border-sky-400"
        }`}
      >
        <span className="truncate">{selectedOption?.label || "Válassz..."}</span>
        <div className={`transition-transform duration-200 text-sky-600 ${isOpen ? "rotate-180" : ""}`} aria-hidden="true">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </button>

      {/* Egyedi Lebegő Dropdown Lista (listbox role) */}
      {isOpen && (
        <div
          role="listbox"
          aria-labelledby={`${selectId}-label`}
          className="absolute z-50 left-0 right-0 mt-2 p-1.5 bg-white/98 backdrop-blur-xl border border-sky-200 rounded-2xl shadow-xl shadow-sky-950/15 max-h-64 overflow-y-auto divide-y divide-slate-100 animate-in fade-in zoom-in-95 duration-150"
        >
          {options.map((option) => {
            const isSelected = String(option.value) === String(value);
            return (
              <div
                key={option.value}
                role="option"
                aria-selected={isSelected}
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    onChange(String(option.value));
                    setIsOpen(false);
                  }
                }}
                onClick={() => {
                  onChange(String(option.value));
                  setIsOpen(false);
                }}
                className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:outline-hidden ${
                  isSelected
                    ? "bg-linear-to-r from-sky-600 to-blue-700 text-white font-bold shadow-sm"
                    : "text-slate-800 hover:bg-sky-50 hover:text-sky-950"
                }`}
              >
                <span>{option.label}</span>
                {isSelected && (
                  <svg className="w-4 h-4 text-white" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
