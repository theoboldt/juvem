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
                    },
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'node_modules/owfont/fonts/',
                        src: '**',
                        dest: 'web/font/',
                        filter: 'isFile'
                    }
                ]
            },
            //disabled until {@see https://github.com/visjs/vis-network/issues/271} is fixed
            jsvis: {
                files: [
                    {
                        expand: true,
                        flatten: true,
                        cwd: 'node_modules/vis-network/standalone/umd',
                        src: 'vis-network.min.j*',
                        dest: 'web/js/',
                        filter: 'isFile'
                    }
                ]

            }
        },

        concat: {
            options: {
                sourceMap: false,
                separator: ''
            },
            distWebJs: {
                src: [
                    '<%= resourcesPath %>/js/cookiechoices.js',
                    'node_modules/jquery/dist/jquery.js',
                    'node_modules/jquery-storage-api/jquery.storageapi.js',
                    'node_modules/jquery-number/jquery.number.js',
                    'node_modules/easymde/dist/easymde.min.js',
                    '<%= resourcesPath %>/js/lib/jquery.filedrop.js',
                    '<%= resourcesPath %>/js/lib/jquery.visible.js',
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
                    'node_modules/bootstrap-table/dist/bootstrap-table.js', //must now be load after bootstrap to make it use correct styles
                    '<%= resourcesPath %>/js/lib/semicolon1.js',
                    'node_modules/bootstrap-table/dist/extensions/natural-sorting/bootstrap-table-natural-sorting.js',
                    'node_modules/bootstrap-table/dist/extensions/resizable/bootstrap-table-resizable.js',
                    'node_modules/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js',
                    'node_modules/bootstrap-table/dist/locale/bootstrap-table-de-DE.js',
                    '<%= resourcesPath %>/js/lib/semicolon2.js',
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
                    '<%= resourcesPath %>/js/markdown.js',
                    '<%= resourcesPath %>/js/area/acquisition_and_event.js',
                    '<%= resourcesPath %>/js/area/acquisition_only.js',
                    '<%= resourcesPath %>/js/area/attendance.js',
                    '<%= resourcesPath %>/js/area/gallery_classic_loader.js',
                    '<%= resourcesPath %>/js/area/gallery_cache_api_loader.js',
                    '<%= resourcesPath %>/js/area/gallery_renderer.js',
                    '<%= resourcesPath %>/js/area/gallery.js',
                    '<%= resourcesPath %>/js/area/event.js',
                    '<%= resourcesPath %>/js/area/dependencies.js',
                    '<%= resourcesPath %>/js/area/employee.js',
                    '<%= resourcesPath %>/js/area/event_export.js',
                    '<%= resourcesPath %>/js/area/participation.js',
                    '<%= resourcesPath %>/js/area/newsletter.js',
                    '<%= resourcesPath %>/js/area/meals.js',
                    '<%= resourcesPath %>/js/main.js'
                ],
                dest: 'web/js/all.js'
            },
            distCssWeb: {
                src: [
                    'node_modules/easymde/dist/easymde.min.css',
                    'var/cache/dep/bootstrap-table-sass.css',
                    'var/cache/dep/all-sass.css',
                    'node_modules/vis-network/dist/vis-network.css',
                    'node_modules/ekko-lightbox/dist/ekko-lightbox.css',
                    'node_modules/jquery-range/jquery.range.css',
                ],
                dest: 'web/css/all.css'
            },
            distCssPrint: {
                src: [
                    'var/cache/dep/all-sass-print.css'
                ],
                dest: 'web/css/print.css'
            },
            distCssOwfont: {
                src: [
                    '<%= resourcesPath %>/css/lib/owfont-regular.css'
                ],
                dest: 'web/css/owfont.css'
            }
        },

        sass: {
            web: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'var/cache/dep/all-sass.css': '<%= resourcesPath %>/scss/web/main.scss',
                    'var/cache/dep/bootstrap-table-sass.css': 'node_modules/bootstrap-table/src/bootstrap-table.scss'
                }
            },
            print: {
                options: {
                    style: 'expanded'
                },
                files: {
                    'var/cache/dep/all-sass-print.css': '<%= resourcesPath %>/scss/print/main.scss'
                }
            }
        },

        jshint: {
            all: ['Gruntfile.js', '<%= resourcesPath %>/js/**/*.js']
        },

        uglify: {
            options: {
                sourceMap: true,
                mangle: {
                    reserved: ['jQuery', 'Backbone']
                }
            },
            js: {
                files: {
                    'web/js/all.min.js': ['web/js/all.js']
                }
            },
            jsvis: {
                files: {
                    'web/js/vis-network.min.js': ['<%= resourcesPath %>/js/lib/vis-network.js']
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
                dest: 'web/css/all.min.css'
            },
            print: {
                options: {
                    shorthandCompacting: false,
                    roundingPrecision: -1,
                    sourceMap: false
                },
                src: 'web/css/print.css',
                dest: 'web/css/print.min.css'
            },
            owfont: {
                options: {
                    shorthandCompacting: false,
                    roundingPrecision: -1,
                    sourceMap: false
                },
                src: 'web/css/owfont.css',
                dest: 'web/css/owfont.min.css'
            }
        },

        watch: {
            js: {
                files: '<%= resourcesPath %>/js/**/*.js',
                tasks: ['clean:js', 'concat:distWebJs', 'uglify:js',  'uglify:jsvis'],
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
            'clean:font', 'copy:font',
            'clean:dep', 'clean:css', 'sass', 'concat:distCssWeb', 'concat:distCssPrint', 'concat:distCssOwfont', 'cssmin',
            'clean:js', 'concat:distWebJs', 'uglify:jsvis', /* disabled until {@see https://github.com/visjs/vis-network/issues/271} is fixed 'copy:jsvis',*/ 'concat:distCssPrint', 'concat:distCssOwfont', 'uglify',
            'clean:dep'
        ]
    );
};
