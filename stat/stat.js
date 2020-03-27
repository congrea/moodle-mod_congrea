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
 * JavaScript library for the getkey stat.
 *
 * @package    local
 * @subpackage getkey
 * @copyright  2014 onwards Pinky sharma  {@link http://vidyamantra.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
var Y_RANGE = 4;
var M_RANGE = 5;
var D_RANGE = 6;

var cfg = {
    'url': "https://c.vidya.io",
    'lKey': '',
    'sDate': '',
    'eDate': ''
}
var congrea_stat = {};
function congrea_stat_init(Y, param) {
    cfg.lKey = param;
    var year = dateFunction('year');
    var month = dateFunction('month');
    var preMonth = dateFunction('premonth');
    var day = dateFunction('day');

    var context = M.congrea_stat_init;
    this.Y = Y;

    Y.one('#id_day_stat').on('click', function() { hightlightButton(Y, '#id_day_stat'); updateData(day.st, day.ed, D_RANGE) });
    Y.one('#id_currmonth_stat').on('click', function() { hightlightButton(Y, '#id_currmonth_stat'); updateData(month.st, month.ed, M_RANGE) });
    Y.one('#id_premonth_stat').on('click', function() { hightlightButton(Y, '#id_premonth_stat'); updateData(preMonth.st, preMonth.ed, M_RANGE) });
    Y.one('#id_year_stat').on('click', function() { hightlightButton(Y, '#id_year_stat'); updateData(year.st, year.ed, Y_RANGE) });
    if (document.getElementById("msggraph").innerHTML == "") {
        hightlightButton(Y, '#id_year_stat');
        updateData(year.st, year.ed, Y_RANGE);
    }
}
// Highlight button
function hightlightButton(Y, btn) {
    Y.all("#option input[type=button]").removeClass('btnhighlight');
    Y.one(btn).addClass("btnhighlight");
}

function updateData(sDate, eDate, range) {
    cfg.sDate = sDate;
    cfg.eDate = eDate;
    cfg.range = range;

    // Load json  data for messages
    d3.jsonp(cfg.url + "?callback={callback}&name=msg&lkey=" + cfg.lKey + "&region[st]=" + cfg.sDate + "&region[et]=" + cfg.eDate + "&range=" + cfg.range, function(jdata) {
        //console.log(jdata);
        if (jdata !== 'NoData') {

            var gData = [];
            var data = {};
            jdata.forEach(function(d) {
                if (cfg.range > Y_RANGE) {
                    var newDate = toTimestamp(d.key[1], d.key[2], d.key[3], d.key[4], d.key[5]);//60
                } else {
                    var newDate = toTimestamp(d.key[1], d.key[2], d.key[3], d.key[4]);//3600
                }
                gData.push({
                    value: d.value,
                    date: newDate
                });
            });
            document.getElementById("msggraph").innerHTML = "";
            if (cfg.range > Y_RANGE) {
                var dataScale = scaleLimit(jdata, cfg.range);//console.log(dataScale);
                data = mergeByProperty(dataScale, gData, 'date');//console.log(data);
            } else {
                data = gData;
            }

            var margin = { top: 10, right: 10, bottom: 100, left: 50 };
            var margin2 = { top: 430, right: 10, bottom: 22, left: 50 };
            mgraphObj = clone(graphObj);
            //console.log('msg '+data);
            mgraphObj.init(cfg, data, "#msggraph", margin, margin2, "m");
            msgGraph(data);
        } else {
            document.getElementById('msggraph').innerHTML = M.util.get_string('nodata', 'mod_congrea', 'message');
        }
    })

    // Load json data for users
    d3.jsonp(cfg.url + "?callback={callback}&name=users&lkey=" + cfg.lKey + "&region[st]=" + cfg.sDate + "&region[et]=" + cfg.eDate + "&range=" + cfg.range, function(jdata) {
        //console.log(jdata);
        if (jdata !== 'NoData') {

            var gData = [];
            var data = {};
            jdata.forEach(function(d) {
                if (cfg.range > Y_RANGE) {
                    var newDate = toTimestamp(d.key[1], d.key[2], d.key[3], d.key[4], d.key[5]);//60
                } else {
                    var newDate = toTimestamp(d.key[1], d.key[2], d.key[3], d.key[4]);//3600
                }
                gData.push({
                    value: d.value.max,
                    avg: (d.value.sum / d.value.count),
                    date: newDate
                });
            });
            document.getElementById("usergraph").innerHTML = "";
            if (cfg.range > Y_RANGE) {
                var dataScale = scaleLimit(jdata, cfg.range);//lowest range for user is month
                data = mergeByProperty(dataScale, gData, 'date');//console.log(data);
            } else {
                data = gData;
            }

            var margin = { top: 10, right: 10, bottom: 100, left: 50 };
            var margin2 = { top: 430, right: 10, bottom: 22, left: 50 };
            ugraphObj = clone(graphObj);
            ugraphObj.init(cfg, data, "#usergraph", margin, margin2, "u");
            userGraph(data);
        } else {
            document.getElementById('usergraph').innerHTML = M.util.get_string('nodata', 'mod_congrea', 'users');
        }
    });
}
var graphObj = {
    cfg: {},
    data: {},
    margin: {},
    margin2: {},
    format: d3.time.format("%Y %m %e %H"),
    divid: "#",
    x: 0,
    x2: 0,
    y: 0,
    y2: 0,
    xAxis: 0,
    xAxis2: 0,
    yAxis: 0,
    graph: null,
    focus: false,
    context: null,
    brush: false,
    area1: false,
    area2: false,
    width: 0,
    height: 0,
    height2: 0,
    div: null,
    error: null,
    title: 'Graph',
    _this: this,

    dateFn: function(d) { return new Date(d.date * 1000); /* timestamp to date*/ },
    valueFn: function(d) { return d.value },

    init: function(cfg, data, divid, margin, margin2, title, callback) {
        this.cfg = cfg;
        this.data = data;
        this.margin = margin;
        this.margin2 = margin2;
        this.divid = divid;
        this.title = title;
        //console.log(data);

        this.width = 960 - this.margin.left - this.margin.right;
        this.height = 500 - this.margin.top - this.margin.bottom;
        this.height2 = 500 - this.margin2.top - this.margin2.bottom;

        this.x = d3.time.scale().range([0, this.width]);
        this.x2 = d3.time.scale().range([0, this.width])
        this.y = d3.scale.linear().range([this.height, 20]);
        this.y2 = d3.scale.linear().range([this.height2, 0]);

        this.xAxis = d3.svg.axis().scale(this.x).orient("bottom").ticks(5);
        this.xAxis2 = d3.svg.axis().scale(this.x2).orient("bottom");
        this.yAxis = d3.svg.axis().scale(this.y).orient("left").ticks(5);

        this.div = d3.select(this.divid).append("div")
            .attr("class", "tooltip")
            .style("opacity", 0);

        this.graph = d3.select(this.divid).append("svg:svg")
            .attr("width", this.width + this.margin.left + this.margin.right)
            .attr("height", 580);
        //.attr("height", this.height + this.margin.top + this.margin.bottom);

        if (title == "m") {
            var gtitle = M.util.get_string('msggraph', 'mod_congrea');
        } else {
            var gtitle = M.util.get_string('usrgraph', 'mod_congrea');
        }
        this.graph.append("text")
            .attr("x", (this.width / 2))
            .attr("y", (17))
            .attr("text-anchor", "middle")
            .style("font-size", "22px")
            .style("text-decoration", "underline")
            .text(gtitle);

        //.append("svg:g").constructor
        this.graph.append("defs").append("clipPath")
            .attr("id", "clip")
            .append("rect")
            .attr("width", this.width)
            .attr("height", this.height);

        this.focus = this.graph.append("g")
            .attr("class", "focus")
            .attr("transform", "translate(" + this.margin.left + "," + this.margin.top + ")");

        this.context = this.graph.append("g")
            .attr("class", "context")
            .attr("transform", "translate(" + this.margin2.left + "," + this.margin2.top + ")");

        var scope = this;
        this.area1 = d3.svg.area()

            .x(function(d) {
                return scope.x(scope.dateFn(d));
            })
            .y0(scope.height)
            .y1(function(d) {
                return scope.y(scope.valueFn(d));
            })

        this.area2 = d3.svg.area()

            .x(function(d) {
                return scope.x2(scope.dateFn(d));
            })
            .y0(scope.height2)
            .y1(function(d) {
                return scope.y2(scope.valueFn(d));
            })

        this.brush = d3.svg.brush().x(this.x2).on("brush", window[title + 'brushed']);
    },
}