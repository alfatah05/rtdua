/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './src/**/*.{vue,js,html}',
  ],
  darkMode: ['selector', '[data-mode="dark"]'],
  theme: {
    extend: {
      colors: {},
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
