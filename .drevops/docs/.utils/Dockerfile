FROM squidfunk/mkdocs-material:9.5.12

ENV DOCKERIZE_VERSION v0.7.0
RUN wget --quiet https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-alpine-linux-amd64-$DOCKERIZE_VERSION.tar.gz

RUN pip install --no-cache-dir mdx_include==1.4.2 mkdocs-same-dir==0.1.2 mkdocs-replace-markdown==0.1.0 mike==1.1.2

WORKDIR /app

COPY . /app

# Start development server by default
ENTRYPOINT ["mkdocs"]
CMD ["serve", "--dev-addr=0.0.0.0:8080"]
