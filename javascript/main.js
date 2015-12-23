/**
 * 爬虫练习Node.js版
 */
"use strict"

const fs         = require('fs');
const superagent = require('superagent');
const cheerio    = require('cheerio');
const async      = require('async');
const url        = require('url');

let company_start = 1;
let company_total = 31068;
let company_page  = 'https://www.itjuzi.com/company/';

let total   = 1000;
let results = [];

console.time("exam");

async.mapLimit(range(company_start, company_total), 5, function (number, callback) {
	let link = company_page + number;
	let data = {};
	superagent.get(link)
		.end(function (err, res) {
			if (err) {
				console.error(err);
			}
			let $ = cheerio.load(res.text);

			data.name     = $('.des-more > div').first().text().trim().substring(7);
			data.location = $('.dbi.marr10.c-gray').text().trim().replace(/\n|\t/g, '');
			data.level    = $('.tag.c').text().trim();
			data.title    = $('.bread li a').last().text().trim();
			data.link     = $('.dbi.linkset.c-gray a').text().trim();
			data.products = [];
			$('.list-prod.limited-itemnum li h4 > b').each(function(i,ele) {
				data.products.push(this.children[0].data);
			});

			superagent.get(data.link).timeout(3000).end(function (err, res) {
				if (err) {
					console.error(err);
				} else {
					let $ = cheerio.load(res.text);
					$('a').each(function(i,ele) {
						let reg = /加入|招聘|诚聘|招贤/;
						let reg2 = /join|zhaopin|job|offer/;
						if (reg.test($(ele).text()) || reg2.test($(ele).attr('href'))) {
							if ($(ele).attr('href'))
								data.jobs_link = url.resolve(data.link, $(ele).attr('href'));
						}
					});
				}

				//console.log(data);
				console.log(number);
				results.push(data);

				if (results.length === total) {
					return callback({}, number);
				}else {
					return callback(null, number);
				}
			});
		});
}, function (err, arr) {
	write();
	console.timeEnd("exam");
});

function range(start, end) {
	let arr = [];
	for ( let i = start; i <= end; i++) {
		arr.push(i);
	}
	return arr;
}

function write() {
	fs.writeFile('companies.json', JSON.stringify(results),  function(err) {
		if (err) {
			return console.error(err);
		}
	});
}

