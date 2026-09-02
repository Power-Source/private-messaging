module.exports = function(grunt) {
    grunt.initConfig({
        cssmin: {
            main: {
                files: [{
                    expand: true,
                    cwd: 'assets',
                    src: ['main.css'],
                    dest: 'assets',
                    ext: '.min.css'
                }]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.registerTask('default', ['cssmin']);

};