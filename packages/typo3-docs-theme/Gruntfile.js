module.exports = function (grunt) {

  const sass = require('sass');

  /**
   * Grunt task to remove source map comment
   */
  grunt.registerMultiTask('removesourcemap', 'Grunt task to remove sourcemp comment from files', function () {
    const done = this.async();
    let counter = 0;
    this.files.forEach(function (file) {
      file.src.forEach(function (filepath) {
        const content = grunt.file.read(filepath).replace(/\/\/# sourceMappingURL=\S+/, '');
        grunt.file.write(file.dest, content);
        grunt.log.success('Source file "' + filepath + '" was processed.');
        counter++;
        if (counter >= file.src.length) done(true);
      });
    });
  });

  /**
   * Project configuration.
   */
  grunt.initConfig({
    paths: {
      source: 'assets/',
      output: 'resources/public/',
      debugDestination: '../../Documentation-GENERATED-temp/_resources/',
    },

    // copy
    copy: {
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

    // sass :: compact, compressed, expanded, nested
    sass: {
      options: {
        implementation: sass,
        outputStyle: 'expanded',
        sourceMap: false
      },
      build: {
        files: {
          '<%= paths.output %>css/theme.css': '<%= paths.source %>sass/theme.scss',
        }
      },

      debug: {
        options: {
          sourceMap: true, // Enable sourcemaps for debugging
        },
        files: {
          '<%= paths.debugDestination %>css/theme.css': '<%= paths.source %>sass/theme.scss',
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
          '<%= paths.output %>js/bootstrap.min.js': '<%= paths.output %>js/bootstrap.min.js'
        }
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
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');

  /**
   * Register tasks
   */
  grunt.registerTask('js', ['uglify']);
  grunt.registerTask('default', ['sass', 'js', 'removesourcemap']);
  grunt.registerTask('build', ['default']);
  grunt.registerTask('debug', ['sass:debug', 'js', 'copy:debug', 'removesourcemap']);
};
