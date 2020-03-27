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
 * JavaScript library for the getkey statistics.
 *
 * @package    local
 * @subpackage getkey
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Function to convert date into timestamp
 * @param int year,month,day,hour,minute,second
 * 
 * @return timestamp
**/

function toTimestamp(year,month,day,hour,minute,second){
    if(typeof(hour) === 'undefined') hour = 0;
    if(typeof(minute) === 'undefined') minute = 0;
    if(typeof(second) === 'undefined') second = 0;
    var datum = new Date(Date.UTC(year,month-1,day,hour,minute,second));
    return datum.getTime()/1000;
}

/**
 * Function to convert date string into timestamp
 * (Since safari does not support inbilt js function)
 * @param date string (yyyy,mm,dd)
 * @return timestamp
**/
function convertStringDate(myDate){
    myDate = myDate.split(",");
    var t = toTimestamp(myDate[0],myDate[1],myDate[2]);
    return t*1000;       
}

/**
 * Function to extend x axis value
 * Return array of object with all missing date(x axis)
 * 
 * Range should be max or min value of dataobject
**/
function scaleLimit(d,range){
    var size = d.length-1;
    if(range > M_RANGE){
        var diff = 60; //minute
        var stpoint = toTimestamp(d[0].key[1],d[0].key[2],d[0].key[3],d[0].key[4],d[0].key[5]);
        var edpoint = toTimestamp(d[size].key[1],d[size].key[2],d[size].key[3],d[size].key[4],d[size].key[5]);
    }else{
        var diff = 3600; //hourly
        var stpoint = toTimestamp(d[0].key[1],d[0].key[2],d[0].key[3],d[0].key[4]);
        var edpoint = toTimestamp(d[size].key[1],d[size].key[2],d[size].key[3],d[size].key[4]);
    }
    var rarray = [];
    while(stpoint <= edpoint){
        //Array of object
        rarray.push({
            value: 0,
            date: stpoint
        });
        stpoint = stpoint+diff; // hourly (3600),min (60)
   }
   return rarray;
}
/**
 * function to merge to array of object
 * Return array of object with all missing date(x axis)
 * Range should be max or min value of dataobject
 * @param array of obj scaled array with empty value
 * @param array of obj graph data array with real value
 * @param  prop mearge basis on it
 * @return array of obj
 */

function mergeByProperty(arr1, arr2, prop) {
    _.each(arr2, function(arr2obj) {
        var arr1obj = _.find(arr1, function(arr1obj) {
            return arr1obj[prop] === arr2obj[prop];
        });

        //If the object already exist extend it with the new values from arr2, otherwise just add the new object to arr1
        arr1obj ? _.extend(arr1obj, arr2obj) : arr1.push(arr2obj);
    });
    return arr1;
}

/**
 *function to clone an object
 * @param object
**/

function clone(obj) {
    if (null == obj || "object" != typeof obj) return obj;
    var copy = obj.constructor();
    for (var attr in obj) {
        if (obj.hasOwnProperty(attr)) copy[attr] = obj[attr];
    }
    return copy;
}

/**
 * Brush for user graph
**/

function ubrushed() {
    ugraphObj.x.domain(ugraphObj.brush.empty() ? ugraphObj.x2.domain() : ugraphObj.brush.extent());
    var data = ugraphObj.data;
    ugraphObj.focus.selectAll(".dot").attr("cx", function(data) { return ugraphObj.x(new Date(data.date*1000)); });
    ugraphObj.focus.select(".area").attr("d", ugraphObj.area1);
    ugraphObj.focus.select(".x.axis").call(ugraphObj.xAxis);
}

/**
 * Message graph brush
**/

function mbrushed() {
    mgraphObj.x.domain(mgraphObj.brush.empty() ? mgraphObj.x2.domain() : mgraphObj.brush.extent());
    var data = mgraphObj.data;
    mgraphObj.focus.selectAll(".dot").attr("cx", function(data) {   return mgraphObj.x(new Date(data.date*1000)); });
    mgraphObj.focus.select(".area").attr("d", mgraphObj.area1);
    mgraphObj.focus.select(".x.axis").call(mgraphObj.xAxis);
}

/**
 *function to add tooltip for msg and user points in graph
 * @param div tooltip div
 * @param object data
 * @param string label
 * @param string prefix m for msg and u for user graph
 **/

function addTooltip(div, data, label, prefix) {

    window[prefix+'graphObj'].focus.selectAll("dot")
    .data(data)
    .enter().append("circle")
    .attr("class", "dot")
    .attr("r", 3)
    .attr("cx", function(data) { return window[prefix+'graphObj'].x(new Date(data.date*1000)); })
    .attr("cy", function(data) { return window[prefix+'graphObj'].y(data.value); })
    .on("mouseover", function(data) {
        div.transition().duration(50).style("opacity", .9);
        div.html(window[prefix+'graphObj'].dateFn(data) + "<br />" + label+" : " + window[prefix+'graphObj'].valueFn(data))
            .style("left", (d3.event.pageX) + "px")
            .style("top", (d3.event.pageY - 28) + "px");

    }).on("mouseout", function(d) {
        div.transition()
            .duration(200)
            .style("opacity", 0);
    });
}

/**
 * Create msg graph
 * @param object data
 **/

function msgGraph(data) {

    var data = data.slice()

    //console.log("start = "+cfg.sDate);console.log("end = "+cfg.eDate);console.log("range = "+cfg.range);

    mgraphObj.x.domain([new Date(convertStringDate(mgraphObj.cfg.sDate)), new Date()]);
    //mgraphObj.x.domain(d3.extent(data,mgraphObj.dateFn));//for future use
    mgraphObj.y.domain(d3.extent(data,mgraphObj.valueFn));
    mgraphObj.x2.domain(mgraphObj.x.domain());
    mgraphObj.y2.domain(mgraphObj.y.domain());

    mgraphObj.focus.append("svg:path").datum(data).attr("class", "area").style("clip-path", "url(#clip)").attr("d", mgraphObj.area1(data));
    mgraphObj.context.append("svg:path").datum(data).attr("class", "area").style("clip-path", "url(#clip)").attr("d", mgraphObj.area2(data));

    addTooltip(mgraphObj.div, data, "Msg",'m');

    mgraphObj.focus.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + mgraphObj.height + ")")
        .call(mgraphObj.xAxis.tickSize(-mgraphObj.height+20, 0, 0));

    mgraphObj.focus.append("g")
        .attr("class", "y axis")
        .call(mgraphObj.yAxis.tickSize(-mgraphObj.width, 0, 0));

    mgraphObj.context.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + mgraphObj.height2 + ")")
        .call(mgraphObj.xAxis2)
        .selectAll("text")
            .style("text-anchor", "end")
            .attr("dx", "-.8em")
            .attr("dy", ".15em")
            .attr("transform", function(d){
                return "rotate(-65)"
            });
    mgraphObj.context.append("g")
        .attr("class", "x brush")
        .call(mgraphObj.brush)
        .selectAll("rect")
        .attr("y", -6)
        .attr("height", mgraphObj.height2 + 7);

    mgraphObj.focus.append("text")
        .attr("text-anchor", "middle")  // this makes it easy to centre the text as the transform is applied to the anchor
        .attr("transform", "translate("+ (mgraphObj.margin.right- mgraphObj.margin.left) +","+(mgraphObj.height/2)+")rotate(-90)")  // text is drawn off the screen top left, move down and out and rotate
        .text("Message");
}


/**
 * Create User graph
 * @param object data
 **/

function userGraph(data) {

    var data = data.slice()

    ugraphObj.x.domain([new Date(convertStringDate(ugraphObj.cfg.sDate)), new Date()]);
    //x.domain(d3.extent(data,dateFn));
    //ugraphObj.y.domain(d3.extent(data,ugraphObj.valueFn));
    ugraphObj.y.domain([0,d3.max(data, function(d) {return Math.max(d.value, d.avg); })]);
    ugraphObj.x2.domain(ugraphObj.x.domain());
    ugraphObj.y2.domain(ugraphObj.y.domain());

    ugraphObj.focus.append("svg:path").datum(data).attr("class", "area").style("clip-path", "url(#clip)").attr("d", ugraphObj.area1(data));
    ugraphObj.context.append("svg:path").datum(data).attr("class", "area").style("clip-path", "url(#clip)").attr("d", ugraphObj.area2(data));

    addTooltip(ugraphObj.div, data, "Max User",'u');

    ugraphObj.focus.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + ugraphObj.height + ")")
        .call(ugraphObj.xAxis.tickSize(-ugraphObj.height+20, 0, 0));

    ugraphObj.focus.append("g")
        .attr("class", "y axis")
        .call(ugraphObj.yAxis.tickSize(-ugraphObj.width, 0, 0));

    ugraphObj.context.append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + ugraphObj.height2 + ")")
        .call(ugraphObj.xAxis2)
        .selectAll("text")
            .style("text-anchor", "end")
            .attr("dx", "-.8em")
            .attr("dy", ".15em")
            .attr("transform", function(d){
                return "rotate(-65)"
            });
    ugraphObj.context.append("g")
        .attr("class", "x brush")
        .call(ugraphObj.brush)
        .selectAll("rect")
        .attr("y", -6)
        .attr("height", ugraphObj.height2 + 7);
    ugraphObj.focus.append("text")
        .attr("text-anchor", "middle")  // this makes it easy to centre the text as the transform is applied to the anchor
        .attr("transform", "translate("+ (ugraphObj.margin.right- ugraphObj.margin.left) +","+(ugraphObj.height/2)+")rotate(-90)")  // text is drawn off the screen top left, move down and out and rotate
        .text("Users");
}


/**
 * Date manupulation function
 * @param string flag (year,month,premonth,day)
 * @return object start and end date 
 **/

function dateFunction(flag) {

    var currY = new Date().getFullYear();
    var currM = new Date().getMonth()+1;
    var currD = new Date().getDate();

    if(flag == 'year'){
        var d = new Date();
        var n = d.getTime()-31536000000; //TODO:handle leap year
        //var t =n/1000;
        var y = new Date(n).getFullYear();
        var M = new Date(n).getMonth()+1;
        var d = new Date(n).getDate();
        return t = { st: y+','+M+','+d , ed:currY+','+(currM+1)+','+currD };

    }else if(flag == 'month'){
        // Next month
        var m = new Date();
        m.setDate(m.getMonth()+2); //month index start from zero
        var nextM = {
            m : new Date(m).getMonth()+2,
            y : new Date(m).getFullYear()
        };
        return t = { st: currY+','+currM+',1', ed:nextM.y+','+nextM.m+',1' };

    }else if(flag == 'day'){
        //Next day
        var d = new Date();
        d.setDate(d.getDate()+1);
        var nextD = {
            d : new Date(d).getDate(),
            m : new Date(d).getMonth()+1,
            y : new Date(d).getFullYear()
        };
        return t = { st: currY+','+currM+','+currD, ed:nextD.y+','+nextD.m+','+nextD.d };

    }else if(flag == 'premonth'){
        //Previous month
        var pm = new Date();
        pm.setDate(pm.getMonth()-1);
        var preM = {
            m : new Date(pm).getMonth(),
            y : new Date(pm).getFullYear()
        };
        return t = { st: preM.y+','+preM.m+',1', ed:currY+','+currM+',1' };
    }
}
