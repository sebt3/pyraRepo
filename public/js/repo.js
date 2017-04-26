(function(global, factory) {
	if (typeof global.d3 !== 'object' || typeof global.d3.version !== 'string')
		throw new Error('repo requires d3v4');
	var v = global.d3.version.split('.');
	if (v[0] != '4')
		throw new Error('repo requires d3v4');
	if (typeof global.bs !== 'object' || typeof global.bs.version !== 'string')
		throw new Error('repo require d3-Bootstrap');
	
	factory(global.repo = global.repo || {}, global);
})(this, (function(repo, global) {
	// private data
	// api definition
	repo.api = repo.api || { }
	repo.chart = repo.chart || { }
	repo.api.format = repo.api.format || { }
	repo.api.format.dateAxe	= function(date) {
		var	locale = d3.timeFormatLocale({
				"dateTime": "%A, le %e %B %Y, %X",
				"date": "%Y-%m-%d",
				"time": "%H:%M",
				"periods": ["AM", "PM"],
				"days": ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
				"shortDays": ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
				"months": ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"],
				"shortMonths": ["janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."]
			}),formatMillisecond	= locale.format(".%L"),
			formatSecond		= locale.format(":%S"),
			formatMinute		= locale.format("%X"),
			formatHour		= locale.format("%X"),
			formatDay		= locale.format("%x"),
			formatWeek		= locale.format("%x"),
			formatMonth		= locale.format("%x"),
			formatYear		= locale.format("%Y");
		return (d3.timeSecond(date) < date ? formatMillisecond
			: d3.timeMinute(date) < date ? formatSecond
			: d3.timeHour(date) < date ? formatMinute
			: d3.timeDay(date) < date ? formatHour
			: d3.timeMonth(date) < date ? (d3.timeWeek(date) < date ? formatDay : formatWeek)
			: d3.timeYear(date) < date ? formatMonth
			: formatYear)(date);
	}
	repo.api.format.date	= function(date) {
		var	locale = d3.timeFormatLocale({
				"dateTime": "%A, le %e %B %Y, %X",
				"date": "%Y-%m-%d",
				"time": "%H:%M",
				"periods": ["AM", "PM"],
				"days": ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"],
				"shortDays": ["dim.", "lun.", "mar.", "mer.", "jeu.", "ven.", "sam."],
				"months": ["janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre"],
				"shortMonths": ["janv.", "févr.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc."]
			});
		return locale.format("%x %X")(date);
	}
	
	repo.panel = function() {
	var title, body, cl = "panel-default";
	function chart(s) { s.each(chart.init); return chart; }
	chart.class	= function(t) {cl = t;return chart;};
	chart.body	= function(t) {body = t;return chart;};
	chart.init	= function() { 
		var root= d3.select(this).append('div').attr('class', 'panel '+cl),
		    bod = root.append('div').attr('class', 'panel-body');
		if (typeof body != 'undefined')
			bod.call(body)
		return chart;
	};
	return chart;
}

	// widgets
	repo.widgets = repo.widgets || { }
	repo.widgets.core = repo.widgets.core || { }
	repo.widgets.core.base = function() {
		var data = {}, called = false, ready=false, root;
		function base(s) { called=true; s.each(base.init); return base; }
		base.dispatch	= d3.dispatch("init", "renderUpdate", "dataUpdate");
		base.inited	= function() {return called; }
		base.ready	= function() {return ready; }
		base.init	= function() { 
			root = d3.select(this);
			base.dispatch.call("init");
			if (ready) {
				base.dispatch.call("renderUpdate");
				/////box.update();
			}
		}
		base.root	= function(_) {
			if (arguments.length) {
				root = _;
				return base;
			} else if (base.inited())
				return root; 
			else
				return false;
		}
		base.data	= function(_) { 
			if (!arguments.length) return data;
			data = _;
			ready=true;
			base.dispatch.call("dataUpdate");
			if (called) {
				base.dispatch.call("renderUpdate");
			}
			return base;
		}
		base.source	= function(_) { 
			if (arguments.length)
				d3.json(_, function(results) { base.data(results); })
			return base;
		}

		return base;
	}
	repo.widgets.core.box  = function() {
		var box = repo.widgets.core.base();
		var bbox = bs.box();
		box.box	= function() {return bbox;}
		box.body	= function(t) {
			if (arguments.length) {
				bbox.body(t);
				return box;
			}
			return bbox.body();
		}
		box.footer	= function(t) {
			if (arguments.length) {
				bbox.footer(t);
				return box;
			}
			return bbox.footer();
		}
		box.title	= function(t) {
			if (arguments.length) {
				bbox.title(t);
				return box;
			}
			return bbox.title();
		}
		box.dispatch.on("init.box.core.widgets", function() {
			box.root().call(bbox);
		});
		box.dispatch.on("dataUpdate.box.core.widgets", function() {
			if (typeof box.data().title != 'undefined') {
				bbox.title(box.data().title);
				bbox.tool({action:'collapse', icon:'fa fa-minus'});
			}
		});
		box.dispatch.on("renderUpdate.box.core.widgets", function() {
			bbox.update();
		});

		return box;
	}
	repo.widgets.items = repo.widgets.items || { }
	repo.widgets.items.pkg = function() {
		var item = repo.widgets.core.base();
		var root;
		item.dispatch.on("init.pkg.items.widgets", function() {
			root = item.root().append('div').attr('class','packageItem');
		});
		item.dispatch.on("renderUpdate.pkg.items.widgets", function() {
			root.html('');
			head = root.append('div').attr('class','packageItem-head').append('a').attr('href', item.data().url);
			head.append('span').attr('class','packageItem-icon').append('img').attr('src',item.data().icon);
			head.append('span').attr('class','packageItem-title').html(item.data().name);
			body = root.append('div').attr('class','packageItem-body').call(bs.descTable()
				.item('uploader', item.data().username)
				.item('version', item.data().version)
				.item('arch', item.data().arch)
			);
		});

		return item;
	}
	repo.widgets.items.app = function() {
		var item = repo.widgets.core.base();
		var root;
		item.dispatch.on("init.app.items.widgets", function() {
			root = item.root().append('div').attr('class','appItem');
		});
		item.dispatch.on("renderUpdate.app.items.widgets", function() {
			root.html('');
			head = root.append('div').attr('class','appItem-head').append('a').attr('href', item.data().url);
			head.append('span').attr('class','appItem-icon').append('img').attr('src',item.data().icon);
			head.append('span').attr('class','appItem-title').html(item.data().name);
			body = root.append('div').attr('class','appItem-body').call(bs.descTable()
				.item('description', item.data().comments)
				.item('package', item.data().dbp_name, item.data().dbp_url)
			);
		});

		return item;
	}
	repo.widgets.items.appFull = function() {
		var item = repo.widgets.core.base();
		var root;
		item.dispatch.on("init.app.items.widgets", function() {
			root = item.root().append('div').attr('class','appItem');
		});
		item.dispatch.on("renderUpdate.app.items.widgets", function() {
			root.html('');
			head = root.append('div').attr('class','appItem-head').append('a').attr('href', item.data().url);
			head.append('span').attr('class','appItem-icon').append('img').attr('src',item.data().icon);
			head.append('span').attr('class','appItem-title').html(item.data().name);
			body = root.append('div').attr('class','appItem-body').call(bs.descTable()
				.item('description', item.data().comments)
				.item('package', item.data().dbp_name, item.data().dbp_url)
				.item('version', item.data().version)
				.item('arch', item.data().arch)
				.item('uploader', item.data().username)
				.item('date', repo.api.format.date(item.data().timestamp))
			);
		});

		return item;
	}
	repo.widgets.list = function() {
		var lst = repo.widgets.core.base(), row = bs.row(), constructor;
		lst.items	= function(t) {
			if (arguments.length) {
				constructor = t;
				return lst;
			}
			return constructor;
		}
		lst.dispatch.on("init.list.widgets", function() {
			lst.root().call(row);
		});
		lst.dispatch.on("dataUpdate.list.widgets", function() {
			var d  = lst.data();
			if(typeof d === 'undefined' || d==null) return;
			d['body'].forEach(function(d) {
				row.cell('col-lg-3 col-md-4 col-sm-6', constructor().data(d));
			});
		});
		lst.dispatch.on("renderUpdate.list.widgets", function() {
			row.update();
		});

		return lst;
	}

	//repo.widgets.pckListItem = 
}));
