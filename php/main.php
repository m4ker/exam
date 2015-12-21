<?php
/**
 * 爬虫PHP版本
 *
 * 面相过程，多线程采集
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');


$company_start = 1;
//$company_total = 31068;
$company_total = 31068;
$company_page  = 'https://www.itjuzi.com/company/%d';

$multi_count = 2; // 并发数量

$results = [];

for ($i = $company_start; $i <= $company_total; $i += $multi_count) {
    //echo $i."\n";
    $data = [];
    $urls = get_urls($company_page, $i, $i + $multi_count);
    $company_contents = fetch_contents($urls);
    foreach($company_contents as $content) {
        $data[] = [
            'name'      => get_company_name($content),
            'products'  => get_company_products($content),
            'location'  => get_company_location($content),
            'level'     => get_company_level($content),
            'homepage'  => get_company_homepage($content)
        ];
    }
    $homepages = array_column($data, 'homepage');
    $homepage_contents = fetch_contents($homepages);
    foreach($homepage_contents as $key => $content) {
        $data[$key]['jobs_link'] = get_company_jobs_link($content, $data[$key]['homepage']);
    }
    //print_r($data);
    foreach ($data as $key => $value) {
        if ($value['jobs_link']) {
            $results[] = $value;
            //echo sprintf("%04d\n", count($results));
            echo $i . ' ' . count($results) . ' ' . $value['jobs_link']."\n";
        }
        if (count($results) >= 1000) {
            file_put_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'companies.json', json_encode($results));
            exit(" done!");
        }
    }
}

function get_company_jobs_link(&$content, &$domain) {
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

            } else if (strpos($link, 'javascript') === 0) {
                $link = '';
            } else if (strpos($link, '/') === 0){
                $link = $domain . $link;
            } else {
                $link = $domain . '/' . $link;
            }
            return $link;
        }
    }
}

function get_company_name(&$content) {
    $p2 = '/<div class="des-more">[^<]*<div>[^<]*<span>([^<]*)<\/span>[^<]*<\/div>/';
    preg_match_all($p2, $content, $out);
    return $out[1] ? mb_substr($out[1][0],7) : '';
}

function get_company_products(&$content) {
    $p = '/<li>[^<]*<a target="_blank" href="[^"]*">[^<]*<h4>[^<]*<span class="[^"]*">[^<]*<\/span>[^<]*<b>([^<]*)<\/b>[^<]*<\/h4>/';
    preg_match_all($p, $content, $out);
    return $out[1];
}

function get_company_location(&$content) {
    $p = '/<div class="dbi marr10 c-gray">[^<]*<a href="[^"]*">[^<]*<\/a>[^<]*<a>[^<]*<\/a>[^<]*<\/div>/';
    preg_match_all($p, $content, $out);
    return $out[0] ? str_replace(["\t","\n"], "", strip_tags($out[0][0])) : '';
}

function get_company_level(&$content) {
    $p = '/<span class="tag c">([^<]*)<\/span>/';
    preg_match_all($p, $content, $out);
    //print_r($out);
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

// 利用curl的multi模式进行并发采集
function fetch_contents($urls, $time_out_ms = 3000) {
    $handles = $contents = [];

    $mh = curl_multi_init();

    //添加curl 批处理会话
    foreach($urls as $key => $url) {
        //$url = sprintf($company_page, $i);
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
        } do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    }

    //获取批处理内容
    foreach($handles as $i => $ch) {
        $content = curl_multi_getcontent($ch);
        $contents[$i] = curl_errno($ch) == 0 ? $content : '';
    }

    foreach($handles as $ch) {
        curl_multi_remove_handle($mh, $ch);
    }

    //关闭批处理句柄
    curl_multi_close($mh);

    return $contents;
}