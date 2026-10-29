# Bản dựng tại chỗ: docker build -t vn-affiliate-shop . && docker run -p 8080:80 -v vnas:/data vn-affiliate-shop
FROM php:8.3-apache
ENV DATA_DIR=/data
COPY . /opt/app
RUN sh /opt/app/docker/apache.sh
VOLUME /data
EXPOSE 80
CMD ["sh", "-c", "sh /opt/app/docker/apache.sh && exec apache2-foreground"]
