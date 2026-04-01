FROM node:18-alpine

WORKDIR /app

# Instala o Quasar CLI globalmente
RUN npm install -g @quasar/cli

# Copia dependências e instala (cache layer)
COPY package*.json ./
RUN if [ -f "package.json" ]; then npm install; fi

# Copia o restante do projeto
COPY . .

EXPOSE 9000

CMD ["quasar", "dev", "--hostname", "0.0.0.0"]
