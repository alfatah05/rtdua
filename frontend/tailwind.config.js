/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{vue,js,html}',
  ],
  darkMode: ['selector', '[data-mode="dark"]'],
  theme: {
    extend: {
      colors: {
        // Token dari dokumen 03 — dipakai via CSS variable di styles
      },
      fontFamily: {
        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        rs: '12px',
        r: '20px',
        rm: '9999px',
      },
    },
  },
  plugins: [],
}
EOF