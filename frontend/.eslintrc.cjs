"use strict";

module.exports = {
  parserOptions: {
    ecmaVersion: "latest",
    sourceType: "module",
  },
  env: {
    browser: true,
    es2021: true,
    node: true,
  },
  extends: ["plugin:vue/vue3-essential", "eslint:recommended"],
  plugins: ["vue"],
  rules: {
    "no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
    "prefer-const": "warn",
    "vue/component-api-style": ["error", ["script-setup"]],
  },
};
