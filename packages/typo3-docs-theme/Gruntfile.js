module.exports = function (grunt) {

  const path = require('path');
  const sass = require('sass');

  /**
   * Grunt task to remove source map comment
   */
  grunt.registerMultiTask('removesourcemap', 'Grunt task to remove sourcemp comment from files', function () {
    var done = this.async(),
      files = this.filesSrc.filter(function (file) {
        return grunt.file.isFile(file);
      }),
      counter = 0;
    this.files.forEach(function (file) {
      file.src.filter(function (filepath) {
        var content = grunt.file.read(filepath).replace(/\/\/# sourceMappingURL=\S+/, '');
        grunt.file.write(file.dest, content);
        grunt.log.success('Source file "' + filepath + '" was processed.');
        counter++;
        if (counter >= files.length) done(true);
      });
    });
  });

  /**
   * Project configuration.
   */
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    paths: {
      source: 'assets/',
      output: 'resources/public/',
      debugDestination: '../../Documentation-GENERATED-temp/_resources/',
    },

    // copy
    copy: {
      css: {
        files: [
          {
            expand: true,
            cwd: 'css',
            src: ['**/*'],
            dest: '<%= paths.output %>css'
          }
        ]
      },
      fonts: {
        files: [
          {
            expand: true,
            cwd: 'node_modules/@fortawesome/fontawesome-free/webfonts',
            src: ['**/*'],
            dest: '<%= paths.output %>fonts'
          },
          {
            expand: true,
            cwd: '<%= paths.source %>fonts',
            src: ['**/*', '!**/*.txt'],
            dest: '<%= paths.output %>fonts'
          }
        ]
      },
      libs: {
        files: [
          {
            src: 'node_modules/@popperjs/core/dist/umd/popper.min.js',
            dest: '<%= paths.output %>js/popper.min.js'
          },
          {
            src: 'node_modules/bootstrap/dist/js/bootstrap.min.js',
            dest: '<%= paths.output %>js/bootstrap.min.js'
          }
        ]
      },

      debug: {
        files: [
          {
            expand: true,
            cwd: '<%= paths.source %>', // Adjust the source directory
            src: ['**/*'],
            dest: '<%= paths.debugDestination %>', // Use the debug destination variable
          },
        ],
      },
    },

    // stylelint
    stylelint: {
      options: {
        configFile: '.stylelintrc',
        fix: true,
      },
      sass: ['sass/**/*.scss']
    },

    // sass :: compact, compressed, expanded, nested
    sass: {
      options: {
        implementation: sass,
        outputStyle: 'expanded',
        sourceMap: false
      },
      build: {
        files: {
          '<%= paths.output %>css/codeblock.css': '<%= paths.source %>sass/codeblock.scss',
          '<%= paths.output %>css/fontawesome.css': '<%= paths.source %>sass/fontawesome.scss',
          '<%= paths.output %>css/theme.css': '<%= paths.source %>sass/theme.scss',
          '<%= paths.output %>css/webfonts.css': '<%= paths.source %>sass/webfonts.scss'
        }
      },

      debug: {
        options: {
          sourceMap: true, // Enable sourcemaps for debugging
        },
        files: {
          '<%= paths.debugDestination %>css/codeblock.css': '<%= paths.source %>sass/codeblock.scss',
          '<%= paths.debugDestination %>css/fontawesome.css': '<%= paths.source %>sass/fontawesome.scss',
          '<%= paths.debugDestination %>css/theme.css': '<%= paths.source %>sass/theme.scss',
          '<%= paths.debugDestination %>css/webfonts.css': '<%= paths.source %>sass/webfonts.scss'
        }
      }
    },

    // uglify
    uglify: {
      options: {
        output: {
          comments: false
        }
      },
      target: {
        files: {
          '<%= paths.output %>js/theme.min.js': [
            '<%= paths.source %>js/*.js',
          ]
        }
      }
    },

    // remove sourcemaps from dist files
    removesourcemap: {
      contrib: {
        files: {
          '<%= paths.output %>js/bootstrap.min.js': '<%= paths.output %>js/bootstrap.min.js',
          '<%= paths.output %>js/popper.min.js': '<%= paths.output %>js/popper.min.js'
        }
      }
    },

    // build
    clean: {
      build: {
        src: [
          '<%= paths.output %>css',
          '<%= paths.output %>fonts',
          '<%= paths.output %>js',
        ]
      }
    },

    // watch
    watch: {
      /* Compile sass changes into theme directory */
      js: {
        files: [
          '<%= paths.source %>js/**/*.js'
        ],
        tasks: ['uglify']
      },
      sass: {
        files: [
          '<%= paths.source %>sass/**/*.scss'
        ],
        tasks: ['sass']
      }
    }

  });

  /**
   * Load tasks
   */
  grunt.loadNpmTasks('grunt-contrib-clean');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-stylelint');

  /**
   * Register tasks
   */
  grunt.registerTask('update', ['copy']);
  grunt.registerTask('js', ['uglify']);
  grunt.registerTask('default', ['clean', 'update', 'stylelint', 'sass', 'js', 'removesourcemap']);
  grunt.registerTask('build', ['default']);
  grunt.registerTask('render', ['clean:build']);
  grunt.registerTask('debug', ['clean', 'update', 'stylelint', 'sass:debug', 'js', 'copy:debug', 'removesourcemap']);
};
