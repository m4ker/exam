<?php
/**
 * 爬虫PHP版本
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

$company_start = 1;
$company_total = 31068;
$company_page  = 'https://www.itjuzi.com/company/%d';

$total = 1000; // 采集数量
$results = []; // 存放采集结果

$start_time = microtime(true);
for ($i = $company_start; $i <= $company_total; $i ++) {
    $content  = fetch_content(sprintf($company_page, $i));
    $homepage = get_company_homepage($content);
    $data = [
        'name'      => get_company_name($content),
        'products'  => get_company_products($content),
        'location'  => get_company_location($content),
        'level'     => get_company_level($content),
        'jobs_link' => $homepage ? get_company_jobs_link(fetch_content($homepage), $homepage) : '',
    ];
    if ($data['name']) {
        $results[] = $data;
        echo $i . ' ' . count($results) . ' '  . $data['name'] . ' ' . $data['jobs_link']."\n";
    }
    // 如果达到采集数量则停止
    if (count($results) >= $total) {
        file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'companies.json', json_encode($results));
        echo "got jobs link: " . count(array_filter(array_column($results, 'jobs_link'))). "\n";
        exit(" done!");
    }

}
echo "use:" . (microtime(true)-$start_time) . "s\n";

// 分析招聘链接
function get_company_jobs_link($content, &$domain) {
    $ps = array(
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*加入[^<]*<\/a>/',
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*加入我们[^<]*<\/a>/',
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*人才招聘[^<]*<\/a>/',
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*招聘信息[^<]*<\/a>/',
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*诚聘英才[^<]*<\/a>/',
        '/<a[^>]*href="([^"]*)"[^>]*>[^<]*招贤纳士[^<]*<\/a>/',
    );
    foreach($ps as $p) {
        if (preg_match_all($p, $content, $out)) {
            $link = $out[1] ? $out[1][0] : '';
            if (strpos($link, 'http') === 0) {
                // do nothing
            } else if (strpos($link, 'javascript') === 0) {
                $link = '';
            } else if (strpos($link, '/') === 0){
                $link = rtrim($domain, '/') . $link;
            } else if (strpos($link, '.') === 0){
                $link = rtrim($domain, '/') . ltrim($link, '.');
            } else {
                $link = rtrim($domain, '/') . '/' . $link;
            }
            return $link;
        }
    }
}

// curl日志
function info_log($msg) {
    file_put_contents(
        dirname(__FILE__) . DIRECTORY_SEPARATOR . 'run.log',
        date("Y-m-d H:i:s\t") . print_r($msg, true),
        FILE_APPEND
    );
}

// 分析公司名
function get_company_name(&$content) {
    $p2 = '/<div class="des-more">[^<]*<div>[^<]*<span>([^<]*)<\/span>[^<]*<\/div>/';
    preg_match_all($p2, $content, $out);
    return $out[1] ? mb_substr($out[1][0],7) : '';
}

// 分析产品名
function get_company_products(&$content) {
    $p = '/<li>[^<]*<a target="_blank" href="[^"]*">[^<]*<h4>[^<]*<span class="[^"]*">[^<]*<\/span>[^<]*<b>([^<]*)<\/b>[^<]*<\/h4>/';
    preg_match_all($p, $content, $out);
    return $out[1];
}

// 分析地区
function get_company_location(&$content) {
    $p = '/<div class="dbi marr10 c-gray">[^<]*<a href="[^"]*">[^<]*<\/a>[^<]*<a>[^<]*<\/a>[^<]*<\/div>/';
    preg_match_all($p, $content, $out);
    return $out[0] ? str_replace(["\t","\n"], "", strip_tags($out[0][0])) : '';
}

// 分析所处阶段
function get_company_level(&$content) {
    $p = '/<span class="tag c">([^<]*)<\/span>/';
    preg_match_all($p, $content, $out);
    return $out[1] ? $out[1][0] : '';
}

function get_company_homepage(&$content) {
    $p = '/<i class="fa fa-link"><\/i> ([^<]*)<\/span>/';
    preg_match_all($p, $content, $out);
    return $out[1] ? $out[1][0] : '';
}

// 生成一批公司信息链接
function get_urls($template, $start, $end) {
    $urls = [];
    for ($i = $start; $i < $end; $i++) {
        $urls[] = sprintf($template, $i);
    }
    return $urls;
}

/*
// 利用curl的multi模式进行并发抓取
function fetch_contents($urls, $time_out_ms = 1000) {
    $handles = $contents = [];

    $mh = curl_multi_init();

    //添加curl 批处理会话
    foreach($urls as $key => $url) {
        $handles[$key] = curl_init($url);
        curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handles[$key], CURLOPT_TIMEOUT_MS, $time_out_ms);
        curl_multi_add_handle($mh, $handles[$key]);
    }

    $active = null;

    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);


    while ($active and $mrc == CURLM_OK) {
        if(curl_multi_select($mh) === -1){
            usleep(100);
            //usleep(10);
        } do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }

    //获取批处理内容
    foreach($handles as $i => $ch) {
        $content = curl_multi_getcontent($ch);
        info_log(curl_getinfo ($ch));
        $contents[$i] = curl_errno($ch) == 0 ? $content : '';
    }

    foreach($handles as $ch) {
        curl_multi_remove_handle($mh, $ch);
    }

    //关闭批处理句柄
    curl_multi_close($mh);

    return $contents;
}
*/

// 单进程抓取页面内容
function fetch_content($url, $time_out_ms = 3000) {
    $content = '';

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($handle, CURLOPT_TIMEOUT_MS, $time_out_ms);
    curl_setopt($handle, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36']);
    $content = curl_exec($handle);
    info_log(curl_getinfo ($handle));
    curl_close($handle);

    return $content;
}