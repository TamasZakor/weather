import React from "react";

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: "primary" | "tab";
  isActive?: boolean;
  isLoading?: boolean;
  children: React.ReactNode;
}

/**
 * Újrafelhasználható gomb időjárás-témájú stílussal (égkék/cián árnyalatok).
 */
export function Button({
  variant = "primary",
  isActive = false,
  isLoading = false,
  disabled,
  children,
  className = "",
  ...props
}: ButtonProps) {
  if (variant === "tab") {
    return (
      <button
        type="button"
        role="tab"
        aria-selected={isActive}
        className={`px-4 py-2 text-sm font-bold rounded-xl transition-all duration-200 cursor-pointer focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:outline-hidden ${
          isActive
            ? "bg-white text-sky-800 shadow-sm border border-sky-200"
            : "text-slate-700 hover:text-slate-900 hover:bg-slate-200/60"
        } ${className}`}
        {...props}
      >
        {children}
      </button>
    );
  }

  return (
    <button
      type="button"
      disabled={isLoading || disabled}
      aria-busy={isLoading}
      className={`w-full py-2.5 px-5 font-bold text-sm text-white bg-linear-to-r from-sky-600 to-blue-700 hover:from-sky-700 hover:to-blue-800 active:from-sky-800 active:to-blue-900 disabled:opacity-50 disabled:cursor-not-allowed rounded-2xl transition-all duration-200 shadow-md shadow-sky-600/25 hover:shadow-lg hover:shadow-sky-600/35 focus-visible:ring-3 focus-visible:ring-sky-600 focus-visible:outline-hidden flex items-center justify-center gap-2 cursor-pointer ${className}`}
      {...props}
    >
      {isLoading && (
        <svg
          className="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
      )}
      {children}
    </button>
  );
}
