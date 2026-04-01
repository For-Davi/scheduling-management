/* eslint-env node */

module.exports = function (/* ctx */) {
  return {
    boot: ['axios', 'auth'],

    css: ['app.scss'],

    extras: ['material-icons'],

    build: {
      vueRouterMode: 'history',
    },

    devServer: {
      open: false,
      port: 9000,
      host: '0.0.0.0',
      proxy: {
        '/api': {
          target: 'http://backend:8000',
          changeOrigin: true,
        },
        '/public': {
          target: 'http://backend:8000',
          changeOrigin: true,
        },
      },
    },

    framework: {
      config: {},
      plugins: ['Notify', 'Dialog', 'Loading'],
    },

    animations: [],
  };
};
