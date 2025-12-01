FROM node:22-slim

COPY .docker/node/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN groupadd -g 1000 admin && \
    useradd -u 1000 -g admin -m admin

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
