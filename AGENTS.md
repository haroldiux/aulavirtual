# AGENTS.md — Aula Virtual LMS UNITEPC

> Instrucciones para Codex Desktop y agentes de IA en este workspace.

## Proyecto Principal

- Aplicacion principal: `Aula-virtual/`
- Fuente de arquitectura: `PLAN_LMS_UNITEPC.md`
- Contexto actual del prototipo: `CONTEXTO_PROYECTO.md`
- Contratos backend previstos: `Aula-virtual/API_CONTRACTS.md`
- Sistema de diseno: `Aula-virtual/DESIGN_SYSTEM.md`

## Flujo Codex / Antigravity + CodeGraph

CodeGraph esta configurado como MCP global de Codex en `C:\Users\harol\.codex\config.toml`:

```toml
[mcp_servers.codegraph]
command = "codegraph"
args = ["serve", "--mcp", "--no-watch"]
```

Y en Antigravity (Gemini) en `C:\Users\harol\.gemini\config\mcp_config.json`:

```json
{
  "mcpServers": {
    "codegraph": {
      "command": "codegraph",
      "args": ["serve", "--mcp", "--no-watch"]
    }
  }
}
```

Como se usa `--no-watch`, actualizar manualmente el indice antes de sesiones grandes. Debido a que el frontend es un submódulo Git y el backend está en la raíz, ambos comparten la misma base de datos de CodeGraph mediante un enlace simbólico (Junction). 

Para actualizar el índice de cada parte, ejecuta:

```powershell
# Sincronizar cambios del backend (PHP/Laravel) en la raíz:
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL"
codegraph sync .

# Sincronizar cambios del frontend (Vue/Quasar) en su subdirectorio:
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL\Aula-virtual"
codegraph sync .
```

Si el indice se bloquea o queda inconsistente, puedes recrearlo ejecutando:

```powershell
# En la raíz (reindexa backend):
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL"
codegraph unlock .
codegraph index .

# En Aula-virtual (reindexa frontend):
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL\Aula-virtual"
codegraph unlock .
codegraph index .
```

## Skills Globales De Codex

Se migraron los skills utiles a `C:\Users\harol\.codex\skills`:

- `aula-virtual-frontend-design-system`
- `aula-virtual-tailwind-design-system`
- `aula-virtual-laravel-patterns`

El skill heredado `quasar` era para Solana, no para Quasar Vue. Quedo renombrado como `quasar-solana-no-usar` y no debe usarse en este LMS.

## Plugins De Codex

- Browser: validar UI local en `http://localhost:9000`.
- Google Drive: usar solo si documentos externos, contratos o planillas viven en Drive.
- Documents, Spreadsheets, Presentations: usar para entregables formales, no para editar codigo.

## Reglas De Trabajo

- No cambiar APIs publicas ni contratos sin actualizar `API_CONTRACTS.md`.
- Backend Laravel 12 iniciado en `back/` (Fase A completa). Levantar con Docker: `cd back && docker compose up -d --build`, luego `docker compose exec app php artisan migrate --seed`. Ver `back/README.md`.
- Mantener Vue 3 Composition API con `<script setup>`, Quasar 2, Pinia, Vue Router y servicios con fallback mock (hasta migrarlos a Axios real contra `back/`).
- Preservar branding UNITEPC y patrones de `DESIGN_SYSTEM.md`.
