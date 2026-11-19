import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import tailwindcss from "@tailwindcss/vite";

// Megjegyzés: A projekt Tailwind CSS v4-et használ.
// A v4-ben a konfiguráció és stílusbetöltés a hivatalos @tailwindcss/vite pluginen
// és a src/index.css-ben lévő `@import "tailwindcss";` direktíván keresztül működik,
// így külön tailwind.config.js / postcss.config.js fájl nem szükséges.
// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    watch: {
      usePolling: true,
    },
  },
});
