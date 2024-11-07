/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        Poppins: ['Poppins', 'sans-serif'],
      },
      colors: {
        sides: 'var(--sides-color)',
        background: 'var(--background-color)',
        text: 'var(--text-color)',
        'text-muted': 'var(--text-muted)',
        primary: 'var(--primary-color)',
        'primary-hover': 'var(--primary-hover-color)',
        secondary: 'var(--secondary-color)',
        'secondary-hover': 'var(--secondary-hover-color)',
        accent: 'var(--accent-color)',
        'accent-hover': 'var(--accent-hover-color)',
        warning: 'var(--warning-color)',
        'warning-hover': 'var(--warning-hover-color)',
        success: 'var(--success-color)',
        'success-hover': 'var(--success-hover-color)',
        border: 'var(--border-color)',
        'border-hover': 'var(--border-color-hover)',
        disabled: 'var(--disabled-color)',
        'disabled-text': 'var(--disabled-text-color)',
        focus: 'var(--focus-color)',
        card: 'var(--card-color)',
        'card-hover': 'var(--card-hover-color)',
        'input-background': 'var(--input-background-color)',
        'input-border': 'var(--input-border-color)',
        'input-focus-border': 'var(--input-focus-border-color)',
        'wrench-color': 'var(--wrench-color)',
        'hand-color': 'var(--hand-color)',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}

