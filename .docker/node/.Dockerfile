FROM node:22-slim

RUN apt-get update \
    && apt-get install -y gosu

COPY .docker/node/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
