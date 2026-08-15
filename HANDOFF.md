# LocalChess 2.0 — Traspaso de contexto

> Este archivo es un puente de contexto entre la conversación donde se construyó esto (en Claude Code, sesión abierta sobre `localranking`) y una sesión nueva abierta directamente sobre este repo (`localchess2`). Podés borrarlo cuando ya no lo necesites — no es documentación permanente del proyecto.

## Qué es esto

Reescritura desde cero de **LocalChess** (gestión de rankings ELO de ajedrez, torneos, competencias y federaciones), reemplazando el proyecto 1.0 (`c:\xampp\htdocs\localranking`, Laravel 11 + Jetstream + Inertia v1, pausado hace 2 años, con deuda técnica documentada en `localranking\Informe_Auditoria_LocalChess.pdf`).

Decisiones tomadas con el usuario al arrancar:
- Repo nuevo separado (no in-place).
- **Multi-tenancy desde el día uno** — el límite de tenant es `Team` (ver abajo).
- **TypeScript** adoptado.
- Import de datos de 1.0: **puntual, una sola vez** (no sync continuo) — comando `import:legacy` ya construido y probado (ver Fase 6).
- **Blog/posts/tags de 1.0 se descartan por completo** (vive en otra app aparte del usuario).

## Stack

Laravel 13.17 + Inertia v3 + Vue 3.5 + TypeScript + Tailwind 4 + shadcn-vue (vía reka-ui) — starter kit oficial de Laravel con Teams. Wayfinder genera helpers de rutas/acciones TS desde el backend en cada build. Pest para tests (no PHPUnit-style). Chart.js + vue-chartjs para gráficos.

**Entorno PHP**: el proyecto necesita PHP ≥8.3 (Laravel 13). Hay un PHP 8.4 standalone completamente configurado en `C:\php84` (todas las extensiones necesarias habilitadas: curl, fileinfo, gd, intl, mbstring, mysqli, openssl, pdo_mysql, pdo_sqlite, sodium, sqlite3, zip) — probado con toda la suite de tests. XAMPP trae PHP 8.2 (muy viejo) y el usuario instaló un PHP 8.5 aparte en `C:\php` que **todavía no tiene php.ini activo** (sin extensiones cargadas → error "could not find driver" en cualquier ruta que toque la base). Recomendación pendiente de aplicar: apuntar el PATH del sistema a `C:\php84` en vez de pelear con el 8.5 sin configurar.

## Decisiones de diseño clave

- **`Team` = Federación**: se conserva el nombre técnico `Team` (primitivo del starter kit: roles Owner/Admin/Member, invitaciones, team switcher) en vez de renombrar a `Federation`, para no pelear contra todo el scaffolding generado. Se extendió con campos de dominio (acronym, city, country, website, `parent_team_id` para jerarquía). `Team.is_personal` — el equipo personal auto-creado por usuario **nunca** otorga permisos de gestión de ajedrez, solo los equipos reales. Este es el fix del bug crítico de autorización de 1.0 (cualquiera que se registraba entraba al panel completo).
- **"Árbitro" NO es un rol de equipo** — es una asignación puntual por torneo (`tournament_arbiters`), igual que en 1.0.
- **Rankings**: `owner_team_id` obligatorio + pivot `ranking_team` para que otras federaciones "adopten" un ranking ajeno.
- **Jugadores** (`players`): entidad global, no pertenecen a una sola federación.
- Motor de ELO portado a `app/Domain/Elo/` (Trf16Parser, EloCalculator, PlayerMatcher, PlayerRosterResolver, TournamentProcessor, CompetitionAggregator) — separado de Eloquent, con tests unitarios con valores FIDE verificados a mano.

## Estado: TODAS las fases del plan están completas

(Fase 0–3 se hicieron en la parte de la conversación que ya no está — resumen: scaffold, esquema multi-tenant con 13 migraciones, auth/autorización con Policies, motor de ELO portado y testeado.)

- **Fase 4** — Páginas núcleo completas y verificadas en navegador (Puppeteer real, no solo tests): Rankings, Federaciones (extensión de `teams/Edit.vue`), Jugadores (CRUD + import/export Excel), Competencias, Torneos (upload TRF16 → wizard de confirmación con matching de jugadores → procesar → standings), nav lateral, Dashboard admin real (métricas + top movers de ELO), **Sitio público completo** (`/{team}/public/...`, sin auth: rankings, jugadores, torneos, competencias — las vistas de torneo/competencia casi duplicadas de 1.0 se unificaron en un solo componente Vue por contexto, corrigiendo el hallazgo 7.3 del informe de auditoría).
- **Fase 5** — Roadmap integrado: gráfico de evolución de ELO (Chart.js) en la ficha pública de jugador, cargado vía **deferred props** de Inertia v3 (no bloquea el render inicial); **polling** en el dashboard admin (`usePoll`, cada 30s, verificado con una request real capturada).
- **Fase 6** — `php artisan import:legacy --owner-email=` — importador completo de la base de 1.0 (conexión `legacy` en `config/database.php`, configurar `LEGACY_DB_*` en `.env` apuntando a una **copia** de la base de 1.0, nunca a la viva). Remapea belief-for-belief cada tabla, corrige bugs de 1.0 en el mismo paso (columnas con typos como `standar_elo`→`standard_elo`, speed en mayúsculas/typo `STANDAR`→`standard`, `id_on_ranking` global→scoped, filas huérfanas sin federación se saltean y quedan en el reporte). Probado end-to-end contra una réplica sintética del schema real de 1.0 en SQLite in-memory (40 aserciones). **Nunca se corrió contra la base real** — eso queda para cuando el usuario decida el corte.

## Verificación final (todo en verde)

130 tests Pest, `npm run build` limpio, `vue-tsc --noEmit` limpio, `eslint` limpio, `prettier --check` limpio, `php vendor/bin/pint --test` limpio, `composer audit` y `npm audit` sin vulnerabilidades.

## Patrones/gotchas importantes para no repetir errores

- **Wayfinder**: rutas con nombre propio (no parte de un `Route::resource`) no aparecen en `routes/{recurso}/index.ts` — hay que importar el controller completo desde `actions/App/Http/Controllers/.../XController` y usar sus métodos (`.form()` para forms, `.url()` para links).
- **Dos rutas → mismo controller con distinto orden de parámetros**: Laravel resuelve el route-model-binding por **posición**, no por nombre, cuando hay parámetros ambiguos entre rutas anidadas y planas. Solución: métodos públicos separados y delgados que delegan a un método privado compartido (ver `PublicTournamentController`).
- **Cast `'date'` de Eloquent**: solo aplica el formato (`'date:Y-m-d'`) en `Model::toArray()`/serialización JSON automática. Si tomás el atributo Carbon y lo metés a mano en un array plano (como en un prop calculado de un controller), **se pierde el cast** y sale el ISO8601 crudo de Carbon. Hay que formatear explícito con `->format('Y-m-d')`.
- Checkbox nativo sin marcar → no se envía en el form; marcado → envía `"on"` (string), no `true`/`false` — la regla `boolean` de Laravel no lo acepta tal cual, hay que normalizar en `prepareForValidation()`.
- Inputs numéricos con configuración regional (es-AR) mandan coma decimal, no punto — normalizar antes de validar (`NormalizesDecimalInput` trait).
- Verificación real de UI: Puppeteer contra Chrome real (no solo tests HTTP), porque atrapó bugs que payloads tipados de Pest no atrapan.

## Dónde mirar si hace falta más detalle

- `Informe_Auditoria_LocalChess.pdf` en `localranking/` — auditoría completa de 1.0 que originó este rebuild.
- Todos los controladores/páginas nuevas están bajo convención estándar Laravel/Inertia (`app/Http/Controllers/`, `resources/js/pages/`).
