# 爬虫练习

## 题目内容

> It 桔子网址: http://www.itjuzi.com  
> 抓取目标：http://itjuzi.com/company/1786  
> 抓取内容：  
>>   1. 产品名称 (如上图的“36 氪 36KR”)  
>>   2. 公司名称(如北京协力筑成金融信息服务有限公司)  
>>   3. 地点(如北京,海淀区)  
>>   4. 阶段(如成长发展期)  
>>   5. 公司招聘页面的 url(http://36kr.com/pages/hire )  
>
> 提交内容  
>>   1. 代码,包括.git(可以放在 github 上)  
>>   2. companies.json:抓取的信息用 json 存在 companies.json 里  
>>   3. readme.txt 写清楚  
>>>     a. 如何抓取 1000 个公司  
>>>     b. 如何抓取招聘页面 url,总共抓取到多少公司的招聘页面 url  
>>>     c. 完成任务花费的时间  
>>>     d. 有 bug 或可以提升的地方  
    

因为最擅长的是PHP，目前正在学习Node，所以打算先用PHP快速实现第一版，如果有剩余时间，再用Node的方式实现第二版。

## 题目分析
    
### 1. 如何采集到公司数据

从公司页面分析得出结论如下：公司采用整数自增，目前最大id为31068，目前共有有效数据25219条，每页有10条记录。所以有如下两种思路：

1. 遍历从1到31068的所有公司页面，采集完所有数据需要31068次请求

2. 遍历列表页，再匹配公司页面，这样采集完所有的数据需要ceil(25219/10)+25219=27741次请求

从效率上来看，2更优，但是从难易程度上，1更有优势，而且流程更容易控制。
    
    
### 2. 如何采集招聘页面

通过对前20个公司网站的观察，发现首页上一般都会有招聘页面的链接，格式如下：

- 加入XX
- 加入我们
- 人才招聘
- 招聘信息
- 诚聘英才
- 招贤纳士

所以可以通过关键字对招聘页面进行匹配。

## 时间记录

- 12-21 21:00 创建git
- 12-21 21:43 分析完毕
- 12-22 00:35 代码已经初步可以运行了，最近半个小时发现it橘子的网站经常502，不知道是不是并发开的太高了。
- 12-22 01:00 调试完毕，发现一些问题
- 经常采集不到数据，需要增加调试信息
- 采用并发策略可能时错误的，并发之后it桔子的延迟会很高，如果要保证抓取成功率，需要降低并发，并且需要重试机制。
- 12-22 10:00 进行第二轮调试。
- 发生了两次比较奇怪的情况，程序执行到一半的时候it桔子所有的链接都瞬间返回了，无法获取内容。
- 要不要这么巧，跑到990个的时候又发生了。。。
- 放弃批量模式，改用单线程。

## 最终执行结果

从1-1115抓到了1000条公司数据，采集到138个招聘页面。

目前已经用掉了6-7个小时，其中有一部分调试时间，每次单线程完整运行大概需要15-20分钟。

对curl的multi模式期望过高，发现里面还有很多坑没有处理好，最初对策略的选择也不正确，产生了不少错误，也浪费了很多时间，整体看来程序的各个方面还有很大的优化空间。

## todolist

- 优化输出效果
- 优化招聘页面匹配规则
- JS生成的页面抓取不到
- it橘子服务器不太稳定，经常超时，如果这样下去可能需要重试机制和缓存机制
- 并发还可以优化 
- 内存使用还可以调整
- 有一些网站可能有防采集策略，增加useragent之后有明显改善，不过还是有优化空间
- 重试机制
- 把“加入会员”也采集进来了，需要处理
- 发现很多公司是重复的，需要一个处理机制
- 公司名称有一些“暂未收录”，可能需要一个策略
- 忘记处理编码了，如果有非utf－8的主页可能会导致抓取失败
