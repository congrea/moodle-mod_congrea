/**
 * Color setting for congrea
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_Congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($) {
    return {
        presetColor: function() {
            $(".form-select.defaultsnext #id_s_mod_congrea_preset").change(function() {
                let val = this.value;
                $('.admin_colourpicker .currentcolour').css('background-color', val);
                $('#id_s_mod_congrea_colorpicker').val(val);
            });

        },
        congreaOnlinePopup: function() {
            $('#overrideform-btn').click(function(e) {
                let url = $(e.target).attr('data-to');
                let expected = $(e.target).attr('data-expected');
                if (Date.now() > expected && expected != 0) {
                    $('.vcbutton').hide();
                    window.location.reload();
                } else {
                    window.open(url, "popupVc");
                }
            });
        },
        congreaPlayRecording: function() {
            $('.playAct-Btn').click(function(e) {
                let url = $(e.target).attr('data-to');
                window.open(url, "popupVc");
            });
        },
        congreaHideJoin: function(timeDiff) {
            $(document).ready(function() {
                let expected, interval;
                if (timeDiff == 0) {
                    expected = 0;
                } else {
                    interval = (timeDiff - 30) * 1000;
                    expected = Date.now() + interval;
                }
                $("#overrideform-btn").attr('data-expected', expected);
            });
        },
    };
});