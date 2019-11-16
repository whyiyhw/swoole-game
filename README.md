## composer  设置国内代理

- `composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/`

## 静态检查
- 对于laravel 5.7 php7.2 以上的版本
- `composer require nunomaduro/phpinsights --dev`
- `./vendor/bin/phpinsights`

## 构建 dockerfile

```dockerfile
FROM php:7.3-fpm

# Version
ENV PHPREDIS_VERSION 4.0.1
ENV SWOOLE_VERSION 4.4.12

# Timezone
RUN /bin/cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo 'Asia/Shanghai' > /etc/timezone
    
# 更换
RUN cd /etc/apt && cp sources.list ./sources.list.bak && rm sources.list && \
    echo "# 默认注释了源码镜像以提高 apt update 速度，如有需要可自行取消注释" >> sources.list && \
    echo "deb https://mirrors.tuna.tsinghua.edu.cn/debian/ buster main contrib non-free" >> sources.list && \
    echo "# deb-src https://mirrors.tuna.tsinghua.edu.cn/debian/ buster main contrib non-free" >> sources.list && \
    echo "deb https://mirrors.tuna.tsinghua.edu.cn/debian/ buster-updates main contrib non-free" >> sources.list && \
    echo "# deb-src https://mirrors.tuna.tsinghua.edu.cn/debian/ buster-updates main contrib non-free" >> sources.list && \
    echo "deb https://mirrors.tuna.tsinghua.edu.cn/debian/ buster-backports main contrib non-free" >> sources.list && \
    echo "# deb-src https://mirrors.tuna.tsinghua.edu.cn/debian/ buster-backports main contrib non-free" >> sources.list && \
    echo "deb https://mirrors.tuna.tsinghua.edu.cn/debian-security buster/updates main contrib non-free" >> sources.list && \
    echo "# deb-src https://mirrors.tuna.tsinghua.edu.cn/debian-security buster/updates main contrib non-free" >> sources.list && \
    apt-get update && \
    apt-get install libfreetype6-dev -y && \
    apt-get install libpng-dev -y && \
    apt-get install libjpeg62-turbo-dev -y && \
    apt-get install libicu-dev -y && \
    apt-get install libmcrypt-dev -y && \
    apt-get install wget -y && \
    apt-get install curl -y && \
    apt-get install git -y  && \
    apt-get install zip -y  && \
    apt-get install libzip-dev -y && \
    apt-get install libssl-dev -y && \
    apt-get install libz-dev -y && \
    apt-get install libnghttp2-dev -y && \
    apt-get install libpcre3-dev -y && \
    apt-get clean -y && \
    apt-get autoremove -y && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include --with-jpeg-dir=/usr/include && \
    docker-php-ext-install gd && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install exif && \
    docker-php-ext-install zip && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install sockets

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer config -g repo.packagist composer https://mirrors.aliyun.com/composer \
    && composer self-update --clean-backups 
    
# Redis extension
RUN wget http://pecl.php.net/get/redis-${PHPREDIS_VERSION}.tgz -O /tmp/redis.tar.tgz \
    && pecl install /tmp/redis.tar.tgz \
    && rm -rf /tmp/redis.tar.tgz \
    && docker-php-ext-enable redis

# Swoole extension
RUN wget https://github.com/swoole/swoole-src/archive/v${SWOOLE_VERSION}.tar.gz -O swoole.tar.gz \
    && mkdir -p swoole \
    && tar -xf swoole.tar.gz -C swoole --strip-components=1 \
    && rm swoole.tar.gz \
    && ( \
    cd swoole \
    && phpize \
    && ./configure --enable-mysqlnd --enable-openssl --enable-sockets --enable-http2 \
    && make -j$(nproc) \
    && make install \
    ) \
    && rm -r swoole \
    && docker-php-ext-enable swoole
      
CMD docker-php-entrypoint php-fpm

EXPOSE 9000
```

## dockerfile 使用
- 在 空白 目录下
- `docker build -t saas-laravel:v2 .`
- 
    ```shell
    docker run -d \
    -v /data/www/swoole-game/:/var/www/html \
    -p 9003:9000 \
    -p 9004:9501 \
    -p 9005:9502 \
    --name game73 --restart=always --privileged=true saas-laravel:v2
    ```
- 对应 `nginx` 配置
```shell
server {
    listen       8080;
    server_name 127.0.0.1;
    root /data/www/ss/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    large_client_header_buffers 4 16k;
    client_max_body_size 30m;
    client_body_buffer_size 128k;

    fastcgi_connect_timeout 300;
    fastcgi_read_timeout 300;
    fastcgi_send_timeout 300;
    fastcgi_buffer_size 64k;
    fastcgi_buffers   4 32k;
    fastcgi_busy_buffers_size 64k;
    fastcgi_temp_file_write_size 64k;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9001;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/html/public/$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    error_log /var/log/nginx/nginx-error.log;
}

```

- 需要注意的是缓存权限问题
- 最需要注意的是，如果 你内外部映射的文件夹 不一样需要 将$realpath_root修改为映射目录的绝对路径
