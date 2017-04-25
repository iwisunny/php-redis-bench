php redis benchmark tool
===

> Wang Xi  <iwisunny@gmail.com>

## 1.简介
- 采用php编写, 模拟redis-benchmark的redis性能测试工具, 专门针对各种php redis库.  
- 本工具基于symfony console, 在cli模式运行.

## 2.安装
> composer install

## 3.使用
`./redis-bench --help`

**注意:如果提示`./redis-bench`权限不够, 执行 `chmod +x redis-bench` **  


### 示例

```
$ ./redis-bench -t set,rpush phpredis --req 10000 

[phpredis:set] completed in 0.781 seconds with 10000 requests, RPS=12812

[phpredis:rpush] completed in 0.763 seconds with 10000 requests, RPS=13099

phpredis: Tests completed in 1.545 seconds, 1.05 MB peak memory usage

```


## License
MIT
