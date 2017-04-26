(function(global, factory) {
	if (typeof global.d3 !== 'object' || typeof global.d3.version !== 'string')
		throw new Error('tables requires d3v4');
	var v = global.d3.version.split('.');
	if (v[0] != '4')
		throw new Error('tables requires d3v4');
	if (typeof global.bs !== 'object' || typeof global.bs.version !== 'string')
		throw new Error('tables require d3-Bootstrap');
	if (typeof global.repo !== 'object')
		throw new Error('tables require repo componant');
	
	factory(global.repo, global);
})(this, (function(repo, global) {
/////////////////////////////////////////////////////////////////////////////////////////////
// wdTableChart
function wdTableBodyChart(pClass) {
	var	chart	= (typeof pClass!="undefined"&&pClass!=null)?pClass:repo.widgets.core.base(),
		keys	= [],
		heads, rows;
	chart.dispatch.on("init.wdTableBodyChart", function() {
		heads = chart.root().selectAll('thead th.sortable').on('click', function(d,i) {
			if(typeof rows == "undefined") return;
			chart.root().selectAll('thead th.sortable').selectAll('i')
				.attr('class', 'fa fa-sort pull-right');
			if (typeof this.sortType == "undefined")	this.sortType = "desc";
			else if (this.sortType == "asc")		this.sortType = "desc";
			else						this.sortType = "asc";
			if (this.sortType == "asc") {
				d3.select(this).select('i').attr('class', 'fa fa-sort-up pull-right');
				rows.sort(function (a,b) {
					if (a[keys[i]] == null) return false;
					if (b[keys[i]] == null) return true;
					if (typeof a[keys[i]] == "object")
						return a[keys[i]].text>b[keys[i]].text;
					return a[keys[i]]>b[keys[i]];
				});
			} else {
				d3.select(this).select('i').attr('class', 'fa fa-sort-down pull-right');
				rows.sort(function (a,b) {
					if (a[keys[i]] == null) return true;
					if (b[keys[i]] == null) return false;
					if (typeof a[keys[i]] == "object")
						return a[keys[i]].text<b[keys[i]].text;
					return a[keys[i]]<b[keys[i]];
				});
			}
		}).append('i').attr('class', 'fa fa-sort pull-right');
	});
	chart.dispatch.on("renderUpdate.wdTableBodyChart", function() {
		var update = chart.root().select('tbody').selectAll('tr').data(chart.data());
		update.exit().remove();
		if(typeof chart.data()[0] == "undefined") return;
		keys = Object.keys(chart.data()[0]);
		rows = update.enter().append('tr').each(function(d){
			if(typeof d.rowProperties == "object" && d.rowProperties != null) {
				if(typeof d.rowProperties.color != "undefined")
					d3.select(this).attr("class", d.rowProperties.color);
			}
		});
		rows.selectAll('td').data(function (d, i) {
			var j=0, ret=[],r={}, haverp = false;
			if (d.hasOwnProperty('rowProperties') && typeof d.rowProperties == "object" && d.rowProperties != null) {
				r = d['rowProperties'];
				haverp=true;
			}
			for (var k in d) {
				if(!d.hasOwnProperty(k)||k=="rowProperties") continue;
				ret.push({ id: ++j, rowid: i, name: k, value: haverp?Object.assign({},r,d[k]):d[k] })
			}
			return ret;
		}).enter().append('td').each(function(d,i) {
			if (d.name =="actions") {
				d3.select(this).attr('class', 'text-right').selectAll('a').data(d.value).enter()
				  .append('a').each(function(p,j) {
					if(typeof p.target != "undefined")
						d3.select(this).attr('data-toggle', 'modal').attr('data-target', p.target)
							.append('i').attr('class', p.icon);
					else
						d3.select(this).attr('href', p.url)
							.append('i').attr('class', p.icon);
					d3.select(this).append('span').text(' ');
				});
			} else if (typeof d.value == "object" && d.value != null) {
				if (typeof d.value.color != "undefined")
					d3.select(this).attr("class", d.value.color);
				if (typeof d.value.icon  != "undefined")
					d3.select(this).append('i').attr('class', d.value.icon)
				if (typeof d.value.url  != "undefined") {
					var a = d3.select(this).append('a')
						.attr('href', d.value.url);
					if (typeof d.value.color != "undefined")
						a.attr('class', d.value.color);
					if (typeof d.value.text == 'number') {
						d3.select(this).classed('text-right',true);
						a.html(' '+wd.format.number(d.value.text));
					} else
						a.html(' '+d.value.text);
				} else {
					if (typeof d.value.text == 'number')
						d3.select(this).classed('text-right',true).append('span').html(' '+wd.format.number(d.value.text));
					else
						d3.select(this).append('span').html(' '+d.value.text);
				}
			} else if (typeof d.value != "undefined") {
				if (typeof d.value == 'number')
					d3.select(this).classed('text-right',true).html(wd.format.number(d.value));
				else
					d3.select(this).html(d.value)
			}
		});
	});
	return chart;
}

repo.chart.table = function() {
	var body = wdTableBodyChart(), heads = [];
	function chart(s) { s.each(chart.init); return chart; }
	chart.body    = function(t) {  body.data(t);return chart;}
	chart.heads   = function(t) {  heads = t;return chart;}
	chart.col     = function(t,c) { if (typeof c == 'undefined') c='sortable';heads.push({ 'text': t, 'class':c});return chart;}
	chart.init    = function() {
		if (d3.select(this).classed('box-body'))
			d3.select(this).classed('table-responsive',true);
		var t = d3.select(this).append('table').attr('class','table table-striped table-hover table-responsive')
		t.append('thead').append('tr').selectAll('th').data(heads).enter().each(function(d,i) {
			d3.select(this).append('th').attr('class',d.class).text(d.text)
		});
		t.append('tbody')
		t.call(body)
	}
	return chart;
}
}));
