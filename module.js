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

/**
 * JavaScript library for the quiz module.
 *
 * @package    mod_congrea
 * @copyright  2020 onwards Manisha Dayal  {@link http://vidyamantra.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_congrea = {

    handleJSONP: function(data) {
        //Y.one("#jpoutput").setHTML(M.util.get_string('keyis', 'local_getkey') +data.key);
        //debugger;
        if (data.error) {
            window.location.href = "getkeyindex.php?e=" + data.error;
        } else {
            var k = data.key;
            var s = data.secret;
            window.location.href = "getkeyindex.php?k=" + data.key + "s=" + data.secret;
        }
    },

    handleFailure: function(data) {
        alert("Ajax request failed");
    },

    submit_form: function(e) {
        var Y = this.Y;
        // Stop the form submitting normally
        e.preventDefault();

        var form = document.getElementsByTagName('form');
        var fname = form[1].firstname.value;
        var lname = form[1].lastname.value;
        var email = form[1].email.value;
        var domain = form[1].domain.value;
        var datacenter = form[1].datacenter.value;
        var fdata = {
            firstname: fname,
            lastname: lname,
            email: email,
            domain: domain,
            datacenter: datacenter // TODO.
        };
        form = Y.JSON.stringify(fdata);

        // If your form has a cancel button, you need to disable it, otherwise it'll be sent with the request
        // and Moodle will think your form was cancelled
        //Y.one('#id_cancel').set('disabled', 'disabled');

        // Send the request
        Y.jsonp('https://www.vidyamantra.com/portal/getvmkey.php?data=' + form, {
            method: 'GET',
            on: {
                success: this.handleJSONP,
                failure: this.handleFailure
            },
            context: this
        });
    },


    init: function(Y) {
        var context = M.mod_congrea;
        this.Y = Y;
        Y.one('#id_submitbutton').on('click', this.submit_form, this);
    }
}