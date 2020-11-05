module.exports = function (grunt) {
    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        assetsPath: 'assets',

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
                    '<%= assetsPath %>/js/cookiechoices.js',
                    'node_modules/jquery/dist/jquery.js',
                    'node_modules/jquery-storage-api/jquery.storageapi.js',
                    'node_modules/jquery-number/jquery.number.js',
                    'node_modules/easymde/dist/easymde.min.js',
                    '<%= assetsPath %>/js/lib/jquery.filedrop.js',
                    '<%= assetsPath %>/js/lib/jquery.visible.js',
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
                    '<%= assetsPath %>/js/lib/semicolon1.js',
                    'node_modules/bootstrap-table/dist/extensions/natural-sorting/bootstrap-table-natural-sorting.js',
                    'node_modules/bootstrap-table/dist/extensions/resizable/bootstrap-table-resizable.js',
                    'node_modules/bootstrap-table/dist/extensions/mobile/bootstrap-table-mobile.js',
                    'node_modules/bootstrap-table/dist/locale/bootstrap-table-de-DE.js',
                    '<%= assetsPath %>/js/lib/semicolon2.js',
                    'node_modules/bootstrap-3-typeahead/bootstrap3-typeahead.js',
                    'node_modules/masonry-layout/dist/masonry.pkgd.js',
                    'node_modules/jquery-range/jquery.range.js',
                    'node_modules/ekko-lightbox/dist/ekko-lightbox.js',
                    'node_modules/imagesloaded/imagesloaded.pkgd.js',
                    'node_modules/justified-layout/dist/justified-layout.js',
                    '<%= assetsPath %>/js/lib/jquery.bsAlerts.min.js',
                    '<%= assetsPath %>/js/tools.js',
                    '<%= assetsPath %>/js/storage.js',
                    '<%= assetsPath %>/js/bootstrap-overrides.js',
                    '<%= assetsPath %>/js/bootstrap-table.js',
                    '<%= assetsPath %>/js/active-button.js',
                    '<%= assetsPath %>/js/markdown.js',
                    '<%= assetsPath %>/js/area/acquisition_and_event.js',
                    '<%= assetsPath %>/js/area/acquisition_only.js',
                    '<%= assetsPath %>/js/area/attendance.js',
                    '<%= assetsPath %>/js/area/gallery_classic_loader.js',
                    '<%= assetsPath %>/js/area/gallery_cache_api_loader.js',
                    '<%= assetsPath %>/js/area/gallery_renderer.js',
                    '<%= assetsPath %>/js/area/gallery.js',
                    '<%= assetsPath %>/js/area/event.js',
                    '<%= assetsPath %>/js/area/dependencies.js',
                    '<%= assetsPath %>/js/area/employee.js',
                    '<%= assetsPath %>/js/area/event_export.js',
                    '<%= assetsPath %>/js/area/participation.js',
                    '<%= assetsPath %>/js/area/newsletter.js',
                    '<%= assetsPath %>/js/area/meals.js',
                    '<%= assetsPath %>/js/main.js'
                ],
                dest: 'web/js/all.js'
            },
            distCssWeb: {
                src: [
                    'node_modules/easymde/dist/easymde.min.css',
                    '../var/cache/dep/bootstrap-table-sass.css',
                    '../var/cache/dep/all-sass.css',
                    'node_modules/vis-network/dist/vis-network.css',
                    'node_modules/ekko-lightbox/dist/ekko-lightbox.css',
                    'node_modules/jquery-range/jquery.range.css',
                ],
                dest: 'web/css/all.css'
            },
            distCssPrint: {
                src: [
                    '../var/cache/dep/all-sass-print.css'
                ],
                dest: 'web/css/print.css'
            },
            distCssOwfont: {
                src: [
                    '<%= assetsPath %>/css/lib/owfont-regular.css'
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
                    '../var/cache/dep/all-sass.css': '<%= assetsPath %>/scss/web/main.scss',
                    '../var/cache/dep/bootstrap-table-sass.css': 'node_modules/bootstrap-table/src/bootstrap-table.scss'
                }
            },
            print: {
                options: {
                    style: 'expanded'
                },
                files: {
                    '../var/cache/dep/all-sass-print.css': '<%= assetsPath %>/scss/print/main.scss'
                }
            }
        },

        jshint: {
            all: ['Gruntfile.js', '<%= assetsPath %>/js/**/*.js']
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
                    'web/js/vis-network.min.js': ['<%= assetsPath %>/js/lib/vis-network.js']
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
                files: '<%= assetsPath %>/js/**/*.js',
                tasks: ['clean:js', 'concat:distWebJs', 'uglify:js',  'uglify:jsvis'],
                options: {
                    livereload: false
                }
            },
            sassWeb: {
                files: ['<%= assetsPath %>/scss/web/**/*.scss', '<%= assetsPath %>/scss/shared/**/*.scss', '<%= assetsPath %>/config/*.scss'],
                tasks: ['sass:web', 'concat:distCssWeb', 'cssmin:web'],
                options: {
                    livereload: false
                }
            },
            sassPrint: {
                files: ['<%= assetsPath %>/scss/print/**/*.scss', '<%= assetsPath %>/scss/shared/**/*.scss', '<%= assetsPath %>/config/*.scss'],
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
