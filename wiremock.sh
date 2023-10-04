#!/usr/bin/env bash

# kill the container on abort
abort() {
  docker container kill seo-gen-wiremock
  exit 0
}

trap 'abort' EXIT

echo "Running script..."
echo "Press Ctrl+C to abort."

docker run -it --rm -d \
  -p 80:8080 \
  --name seo-gen-wiremock \
  -v $PWD/stubs:/home/wiremock \
  wiremock/wiremock:2.32.0 --verbose

php7.1 -c docker/php.ini -S 127.0.0.1:8787
