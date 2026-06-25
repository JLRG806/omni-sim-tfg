#!/bin/bash
# scripts/n8n-sync.sh
# Sincroniza los workflows de n8n/ con el servidor n8n en vertexlab
# usando la API REST de n8n — sin entrar al servidor ni a la UI.
#
# Uso:
#   N8N_API_KEY=<tu_key> ./scripts/n8n-sync.sh
#   o guarda la key en .n8n-api-key (gitignoreado):
#   echo "tu_key" > .n8n-api-key && ./scripts/n8n-sync.sh
#
# Primera vez:
#   1. Abre http://100.108.45.35:5678  (admin / omnisim_n8n)
#   2. Settings → n8n API → Create an API Key → copia la key

set -e

N8N_URL="http://100.108.45.35:5678"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
WORKFLOWS_DIR="$SCRIPT_DIR/../n8n"
KEY_FILE="$SCRIPT_DIR/../.n8n-api-key"

# Obtener API key: env var > archivo > error
if [ -n "$N8N_API_KEY" ]; then
  API_KEY="$N8N_API_KEY"
elif [ -f "$KEY_FILE" ]; then
  API_KEY=$(cat "$KEY_FILE" | tr -d '[:space:]')
else
  echo "Error: N8N_API_KEY no definida."
  echo ""
  echo "Opciones:"
  echo "  1. N8N_API_KEY=<key> ./scripts/n8n-sync.sh"
  echo "  2. echo '<key>' > .n8n-api-key"
  echo ""
  echo "Obtener la key en: $N8N_URL/settings/api"
  exit 1
fi

# Función helper: llamada a la API
n8n_api() {
  local method=$1
  local endpoint=$2
  shift 2
  curl -s -X "$method" \
    -H "X-N8N-API-KEY: $API_KEY" \
    -H "Content-Type: application/json" \
    "$N8N_URL/api/v1$endpoint" \
    "$@"
}

# Verificar conexión
echo "=== Sincronizando workflows → n8n en vertexlab ==="
echo "   URL: $N8N_URL"
echo ""

health=$(curl -s -o /dev/null -w "%{http_code}" "$N8N_URL/healthz")
if [ "$health" != "200" ]; then
  echo "Error: n8n no responde en $N8N_URL (HTTP $health)"
  echo "Verifica que n8n esté corriendo: docker --context vertexlab compose -f docker/docker-compose.n8n.yml ps"
  exit 1
fi

# Obtener lista de workflows existentes
existing_json=$(n8n_api GET "/workflows?limit=100")

for json_file in "$WORKFLOWS_DIR"/*.json; do
  [ -f "$json_file" ] || continue
  name=$(python3 -c "import sys,json; print(json.load(open('$json_file'))['name'])" 2>/dev/null || basename "$json_file" .json)
  echo "→ $name"

  # Buscar si ya existe por nombre
  existing_id=$(echo "$existing_json" | \
    python3 -c "
import sys,json
d=json.load(sys.stdin)
r=[w for w in d.get('data',[]) if w.get('name')=='$name']
print(r[0]['id'] if r else '')
" 2>/dev/null)

  if [ -z "$existing_id" ]; then
    # Crear nuevo workflow
    result=$(n8n_api POST "/workflows" -d @"$json_file")
    workflow_id=$(echo "$result" | python3 -c "import sys,json; print(json.load(sys.stdin).get('id','ERROR'))")
    echo "  ✓ Creado (id: $workflow_id)"
  else
    # Actualizar workflow existente
    result=$(n8n_api PUT "/workflows/$existing_id" -d @"$json_file")
    workflow_id="$existing_id"
    echo "  ✓ Actualizado (id: $existing_id)"
  fi

  # Activar workflow
  if [ "$workflow_id" != "ERROR" ] && [ -n "$workflow_id" ]; then
    n8n_api POST "/workflows/$workflow_id/activate" > /dev/null
    echo "  ✓ Activado"
  fi
  echo ""
done

echo "=== Sincronización completada ==="
