/**
 * 爬虫练习Node.js版
 */
"use strict"

const fs         = require("fs");
const superagent = require('superagent');
const cheerio    = require('cheerio');
const async      = require('async');

let company_start = 1;
let company_total = 31068;
let company_page  = 'https://www.itjuzi.com/company/';

let total   = 5;
let results = [];

async.mapLimit(range(company_start, company_total), 2, function (number, callback) {
	let url = company_page + number;
	let data = {};
	superagent.get(url)
		.end(function (err, res) {
			if (err) {
				return console.error(err);
			}
			let $ = cheerio.load(res.text);

			data.name = $('.des-more > div').first().text().trim().substring(7);
			data.location = $('.dbi.marr10.c-gray').text().trim().replace(/\n|\t/g, '');
			data.level = $('.tag.c').text().trim();
			data.title = $('.bread li a').last().text().trim();
			data.link  = $('.dbi.linkset.c-gray a').text().trim();
			data.products = [];
			$('.list-prod.limited-itemnum li h4 > b').each(function(i,ele) {
				data.products.push(this.children[0].data);
			});

			console.log(data);
			results.push(data);

			if (results.length === total)
				return callback({}, number);
			else
				return callback(null, number);

		});
}, function (err, arr) {
	write();
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

function get_job_link() {

}