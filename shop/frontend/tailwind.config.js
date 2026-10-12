/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        page: '#FFFFFF',
        surface: '#F7F7F5',
        ink: {
          DEFAULT: '#111827',
          muted: '#6B7280',
        },
        line: '#E5E7EB',
        brand: {
          DEFAULT: 'var(--brand, #2F5D50)',
          hover: 'var(--brand-hover, #264a40)',
          light: 'var(--brand-light, #f0f5f3)',
        },
        accent: '#E8A317',
        success: '#15803D',
        error: '#B91C1C',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      borderRadius: {
        card: '16px',
        input: '12px',
        pill: '9999px',
      },
      boxShadow: {
        soft: '0 6px 24px rgba(17, 24, 39, 0.06)',
      },
      maxWidth: {
        container: '1280px',
      },
    },
  },
  plugins: [],
}
