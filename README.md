# Location des Bernardins

## Docker

```bash
docker run -d \
  -p 80:80 \
  --name location-des-bernardins \
  -v "$PWD":/var/www/html \
  php:7.3-apache
```
