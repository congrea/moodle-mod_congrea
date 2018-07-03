/**
 * Color setting for congrea
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_Congrea
 * @copyright  2018 Ravi Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function ($){
    return {
        presetcolor: function (){
            $(".form-select.defaultsnext #id_s_mod_congrea_preset").change(function (){
                var val = this.value;
                $('.admin_colourpicker .currentcolour').css('background-color', val);
                $('#id_s_mod_congrea_colorpicker').val(val);
            });

        },
        congrea_online_popup: function (){
            $('#overrideform').submit(function (){
                var newTab = window.open('', 'popupVc');
                if (window.newTab && window.newTab.closed === false) {
                    newTab.focus();
                    return false;
                }
                $(this).attr('target', 'popupVc');
                if (!newTab) {
                    return;
                }
                newTab.focus();
                return newTab;
            });
        },
        congrea_play_recording: function (){
            $('.playAct').submit(function (){
                var newTab = window.open('', 'popupVc');
                if (window.newTab && window.newTab.closed === false) {
                    newTab.focus();
                    return false;
                }
                $(this).attr('target', 'popupVc');
                if (!newTab) {
                    return;
                }
                newTab.focus();
                return newTab;
            });
        }

    };
});