/* eslint-env node */
module.exports = {
  root: true,
  env: { browser: true, es2022: true },
  extends: [
    'plugin:vue/vue3-recommended',
    '@vue/eslint-config-typescript',
    '@vue/eslint-config-prettier',
  ],
  parserOptions: { ecmaVersion: 'latest', sourceType: 'module' },
  rules: {
    'vue/multi-word-component-names': 'off',
  },
  ignorePatterns: ['dist', 'node_modules', 'coverage', '*.config.*'],
  overrides: [
    {
      files: ['src/components/ui/**/*.vue'],
      rules: {
        'vue/require-default-prop': 'off',
      },
    },
  ],
}
