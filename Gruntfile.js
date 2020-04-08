// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/* jshint node: true, browser: false */
/* eslint-env node */

/**
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */

module.exports = function(grunt) {
    require('load-grunt-tasks')(grunt, { pattern: ['grunt-contrib-*', 'grunt-shell'] });
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        /*         eslint: {
                    options: {
                        //configFile: "conf/eslint.json",
                        //rulePaths: ["conf/rules"],
                        silent: true
                    },
                    build: ['amd/src/congrea.js']
                }, */
        babel: {
            options: {
                sourceMap: true,
                presets: ["@babel/preset-env"]
            },
            build: {
                src: 'amd/src/congrea.js',
                dest: 'amd/build/congrea.min.js'
            }
        },
        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
            },
            build: {
                src: 'amd/build/congrea.min.js',
                dest: 'amd/build/congrea.min.js'
            }
        }
    });
    // Load the plugins.
    //grunt.loadNpmTasks('gruntify-eslint');
    grunt.loadNpmTasks('grunt-babel');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    // Default task(s).
    //grunt.registerTask('default', ['eslint', 'babel', 'uglify']);
    grunt.registerTask('default', ['babel', 'uglify']);
};