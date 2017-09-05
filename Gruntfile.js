module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        resourcesPath: 'app/Resources',

        clean: {
            dep: ['app/dep/*'],
            css: ['web/css/*'],
            font: ['web/font/*'],
            js: ['web/js/*']
        },

        copy: {
            font: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'node_modules/bootstrap-sass/assets/fonts/bootstrap/',
                        src: '**',
                        dest: 'web/font/',
                        filter: 'isFile'
                    }
                ]
            }
        },

        concat: {
            options: {
                sourceMap: true,
                separator: ';'
            },
            distJs: {
                src: [
                    '<%= resourcesPath %>/js/cookiechoices.js',
                    'node_modules/jquery/dist/jquery.js',
                    'node_modules/jquery-storage-api/jquery.storageapi.js',
                    '<%= resourcesPath %>/js/lib/jquery.filedrop.js',
                    'node_modules/bootstrap-table/src/bootstrap-table.js',
                    'node_modules/bootstrap-table/src/locale/bootstrap-table-de-DE.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/button.js',
//                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/popover.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/transition.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/collapse.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/scrollspy.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
//                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/affix.js',
                    'node_modules/jquery-range/jquery.range.js',
                    '<%= resourcesPath %>/js/lib/jquery.bsAlerts.min.js',
                    '<%= resourcesPath %>/js/tools.js',
                    '<%= resourcesPath %>/js/user-settings.js',
                    '<%= resourcesPath %>/js/bootstrap-overrides.js',
                    '<%= resourcesPath %>/js/bootstrap-table.js',
                    '<%= resourcesPath %>/js/active-button.js',
                    '<%= resourcesPath %>/js/area/acquisition.js',
                    '<%= resourcesPath %>/js/area/event.js',
                    '<%= resourcesPath %>/js/area/newsletter.js',
                    '<%= resourcesPath %>/js/main.js'
                ],
                dest: 'web/js/all.js'
            },
            distCss: {
                src: [
                    'app/cache/dep/all-sass.css',
                    'node_modules/jquery-range/jquery.range.css',
                    'node_modules/bootstrap-table/src/bootstrap-table.css'
                ],
                dest: 'web/css/all.css'
            }
        },

        sass: {
            dist: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'app/cache/dep/all-sass.css': '<%= resourcesPath %>/scss/main.scss'
                }
            }
        },

        jshint: {
            all: ['Gruntfile.js', '<%= resourcesPath %>/js/**/*.js']
        },

        uglify: {
            options: {
                sourceMap: false,
                mangle: {
                    except: ['jQuery', 'Backbone']
                }
            },
            js: {
                files: {
                    'web/js/all.min.js': ['web/js/all.js']
                }
            }
        },

        cssmin: {
            options: {
                shorthandCompacting: false,
                roundingPrecision: -1,
                sourceMap: false
            },
            target: {
                files: {
                    'web/css/all.min.css': ['web/css/all.css']
                }
            }
        },

        watch: {
            js: {
                files: '<%= resourcesPath %>/js/**/*.js',
                tasks: ['clean:js', 'concat:distJs', 'uglify'],
                options: {
                    livereload: true
                }
            },
            sass: {
                files: ['<%= resourcesPath %>/scss/**/*.scss', '<%= resourcesPath %>/config/*.scss'],
                tasks: ['sass', 'concat:distCss', 'cssmin'],
                options: {
                    livereload: true
                }
            }
        }

    });

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');


    grunt.registerTask('default', ['watch']);
    grunt.registerTask(
        'deploy',
        [
            'clean:font', 'copy',
            'clean:dep', 'clean:css', 'sass', 'concat:distCss', 'cssmin',
            'clean:js', 'concat:distJs', 'uglify',
            'clean:dep'
        ]
    );
};