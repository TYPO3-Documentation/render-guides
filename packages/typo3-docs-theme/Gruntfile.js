module.exports = function (grunt) {

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
    }

  });

  /**
   * Load tasks
   */
  grunt.loadNpmTasks('grunt-contrib-copy');

  /**
   * Register tasks
   */
  grunt.registerTask('debug', ['copy:debug']);
};
