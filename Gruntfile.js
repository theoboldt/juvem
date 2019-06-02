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
                separator: ''
            },
            distWebJs: {
                src: [
                    '<%= resourcesPath %>/js/cookiechoices.js',
                    'node_modules/jquery/dist/jquery.js',
                    'node_modules/jquery-storage-api/jquery.storageapi.js',
                    'node_modules/jquery-number/jquery.number.js',
                    '<%= resourcesPath %>/js/lib/jquery.filedrop.js',
                    '<%= resourcesPath %>/js/lib/jquery.visible.js',
                    'node_modules/bootstrap-table/src/bootstrap-table.js',
                    'node_modules/bootstrap-table/src/locale/bootstrap-table-de-DE.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/dropdown.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/alert.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/button.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tab.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/popover.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/transition.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/collapse.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/scrollspy.js',
                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/modal.js',
//                    'node_modules/bootstrap-sass/assets/javascripts/bootstrap/affix.js',
                    'node_modules/bootstrap-3-typeahead/bootstrap3-typeahead.js',
                    'node_modules/masonry-layout/dist/masonry.pkgd.js',
                    'node_modules/jquery-range/jquery.range.js',
                    'node_modules/ekko-lightbox/dist/ekko-lightbox.js',
                    'node_modules/imagesloaded/imagesloaded.pkgd.js',
                    'node_modules/justified-layout/dist/justified-layout.js',
                    '<%= resourcesPath %>/js/lib/jquery.bsAlerts.min.js',
                    '<%= resourcesPath %>/js/tools.js',
                    '<%= resourcesPath %>/js/storage.js',
                    '<%= resourcesPath %>/js/bootstrap-overrides.js',
                    '<%= resourcesPath %>/js/bootstrap-table.js',
                    '<%= resourcesPath %>/js/active-button.js',
                    '<%= resourcesPath %>/js/area/acquisition_and_event.js',
                    '<%= resourcesPath %>/js/area/acquisition_only.js',
                    '<%= resourcesPath %>/js/area/gallery_classic_loader.js',
                    '<%= resourcesPath %>/js/area/gallery_cache_api_loader.js',
                    '<%= resourcesPath %>/js/area/gallery_renderer.js',
                    '<%= resourcesPath %>/js/area/gallery.js',
                    '<%= resourcesPath %>/js/area/event.js',
                    '<%= resourcesPath %>/js/area/employee.js',
                    '<%= resourcesPath %>/js/area/event_export.js',
                    '<%= resourcesPath %>/js/area/participation.js',
                    '<%= resourcesPath %>/js/area/newsletter.js',
                    '<%= resourcesPath %>/js/main.js'
                ],
                dest: 'web/js/all.js'
            },
            distCssWeb: {
                src: [
                    'app/cache/dep/all-sass.css',
                    'node_modules/ekko-lightbox/dist/ekko-lightbox.css',
                    'node_modules/jquery-range/jquery.range.css',
                    'node_modules/bootstrap-table/src/bootstrap-table.css'
                ],
                dest: 'web/css/all.css'
            },
            distCssPrint: {
                src: [
                    'app/cache/dep/all-sass-print.css'
                ],
                dest: 'web/css/print.css'
            }
        },

        sass: {
            web: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'app/cache/dep/all-sass.css': '<%= resourcesPath %>/scss/web/main.scss'
                }
            },
            print: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'app/cache/dep/all-sass-print.css': '<%= resourcesPath %>/scss/print/main.scss'
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
                    reserved: ['jQuery', 'Backbone']
                }
            },
            js: {
                files: {
                    'web/js/all.min.js': ['web/js/all.js']
                }
            }
        },

        cssmin: {
            web: {
                options: {
                    shorthandCompacting: false,
                    roundingPrecision: -1,
                    sourceMap: false
                },
                src: 'web/css/all.css',
                dest:'web/css/all.min.css'
            },
            print: {
                options: {
                    shorthandCompacting: false,
                    roundingPrecision: -1,
                    sourceMap: false
                },
                src: 'web/css/print.css',
                dest:'web/css/print.min.css'
            }
        },

        watch: {
            js: {
                files: '<%= resourcesPath %>/js/**/*.js',
                tasks: ['clean:js', 'concat:distWebJs', 'uglify'],
                options: {
                    livereload: false
                }
            },
            sassWeb: {
                files: ['<%= resourcesPath %>/scss/web/**/*.scss', '<%= resourcesPath %>/scss/shared/**/*.scss', '<%= resourcesPath %>/config/*.scss'],
                tasks: ['sass:web', 'concat:distCssWeb', 'cssmin:web'],
                options: {
                    livereload: false
                }
            },
            sassPrint: {
                files: ['<%= resourcesPath %>/scss/print/**/*.scss', '<%= resourcesPath %>/scss/shared/**/*.scss', '<%= resourcesPath %>/config/*.scss'],
                tasks: ['sass:print', 'concat:distCssPrint', 'cssmin:print']
            }
        }

    });

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-sass');
    grunt.loadNpmTasks('grunt-contrib-uglify-es');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-watch');


    grunt.registerTask('default', ['deploy', 'watch']);
    grunt.registerTask(
        'deploy',
        [
            'clean:font', 'copy',
            'clean:dep', 'clean:css', 'sass', 'concat:distCssWeb', 'concat:distCssPrint', 'cssmin',
            'clean:js', 'concat:distWebJs', 'concat:distCssPrint', 'uglify',
            'clean:dep'
        ]
    );
};
