let gulp = require('gulp');
let browserSync = require('browser-sync').create();
const { exec} = require('child_process');

let hostUrl = process.env.DDEV_HOSTNAME ?? 'localhost';
// @TODO - Check how this operates without ddev

browserSyncOptions = {
  server: false,
  proxy: {
    target: 'localhost',
    proxyRes: [
      function (proxyRes, req, res) {
        if (!req.url.match(/\/(_resources\/|favicon.ico)/g)) {
          console.log('Proxy: ' + req.url);
        }
      }
    ]
  },
  host: hostUrl,
  open: false,
  ui: false,
  logFileChanges: false
};

gulp.task('serve', function () {
  browserSync.init(browserSyncOptions);
  gulp.watch("../../Documentation/**/*.rst").on('change', function (e) {
    console.log('Render RST: ' + e);

    exec('cd ../.. && make docs ENV=local', function (error, stdout, stderr) {
      console.log(stdout);
      browserSync.reload();
    });
  });
  gulp.watch("../../Documentation-GENERATED-temp/**/*.html").on('change', function (e) {
    console.log('Reload HTML: ' + e);
    browserSync.reload();
  });
});

