FROM node:22-alpine

WORKDIR /app

# Copiar manifiestos e instalar dependencias dentro del contenedor.
# Binarios nativos de Vite/rolldown se compilan para musl (Alpine).
# Capa cacheada: solo se reinstala si package*.json cambia.
COPY package*.json ./
RUN npm install

EXPOSE 5173

CMD ["npm", "run", "dev"]
