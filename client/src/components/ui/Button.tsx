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
        className={`px-4 py-2 text-sm font-semibold rounded-lg transition-all duration-200 cursor-pointer ${
          isActive
            ? "bg-white text-sky-700 shadow-sm border border-sky-100"
            : "text-slate-600 hover:text-slate-900 hover:bg-slate-200/50"
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
      className={`w-full py-2.5 px-5 font-semibold text-sm text-white bg-linear-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 active:from-sky-700 active:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl transition-all duration-200 shadow-md shadow-sky-500/20 hover:shadow-lg hover:shadow-sky-500/30 flex items-center justify-center gap-2 cursor-pointer ${className}`}
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
