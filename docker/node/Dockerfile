FROM node:6-alpine

ENV NODE_ENV=development

WORKDIR /code

CMD echo "Install node packages" && \
  npm i && \
  echo "Start dev server with hot reload" && \
  ./node_modules/.bin/encore dev-server --host 0.0.0.0 --port 8082 --disable-host-check --hot