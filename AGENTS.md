# AGENTS.md — Aula Virtual LMS UNITEPC

> Instrucciones para Codex Desktop y agentes de IA en este workspace.

## Proyecto Principal

- Aplicacion principal: `Aula-virtual/`
- Fuente de arquitectura: `PLAN_LMS_UNITEPC.md`
- Contexto actual del prototipo: `CONTEXTO_PROYECTO.md`
- Contratos backend previstos: `Aula-virtual/API_CONTRACTS.md`
- Sistema de diseno: `Aula-virtual/DESIGN_SYSTEM.md`

## Flujo Codex + CodeGraph

CodeGraph esta configurado como MCP global de Codex en `C:\Users\harol\.codex\config.toml`:

```toml
[mcp_servers.codegraph]
command = "codegraph"
args = ["serve", "--mcp", "--no-watch"]
```

Como se usa `--no-watch`, actualizar manualmente el indice antes de sesiones grandes:

```powershell
cd "C:\PROYECTOS\PROYECTO AULA VIRTUAL\Aula-virtual"
codegraph sync .
```

Si el indice se bloquea o queda inconsistente:

```powershell
codegraph unlock .
codegraph index .
codegraph context "tarea concreta"
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
- No iniciar backend Laravel en esta fase salvo instruccion explicita.
- Mantener Vue 3 Composition API con `<script setup>`, Quasar 2, Pinia, Vue Router y servicios con fallback mock.
- Preservar branding UNITEPC y patrones de `DESIGN_SYSTEM.md`.
