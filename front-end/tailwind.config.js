/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./index.html", "./src/**/*.{html,js}"],
  theme: {
    extend: {
      colors: {
        charcoal: "#2C2C2C",
        teal: "#0AB0BF",
        blue: "#0069CE",
        "dark-teal": "#00838F",
        orange: "#FF8853",
      },
      fontFamily: {
        sans: ['"Open Sans"', "ui-sans-serif", "system-ui", "sans-serif"],
      },
      fontSize: {
        h1: ["40px", "48px"],
        h2: ["32px", "48px"],
        h3: ["24px", "32px"],
        h4: ["20px", "28px"],
        body: ["16px", "28px"],
        link: ["16px", "28px"],
      },
    },
  },
  plugins: [],
};
