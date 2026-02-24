/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        // DNSC brand palette
        dnsc: {
          navy:  '#0B1F3A',
          blue:  '#1E90FF',
          light: '#60B0FF',
          bg:    '#F0F2F5',
        }
      },
      fontFamily: {
        display: ['Sora', 'sans-serif'],
        body:    ['Sora', 'sans-serif'],
        mono:    ['"IBM Plex Mono"', 'monospace'],
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.5rem',
      },
    },
  },
  plugins: [],
}