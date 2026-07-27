                    Arquitectura recomendada

                    Scheduler (cada 30 min)

                           Laravel
                     (Jobs + Queues + AI SDK)
                             │
          ┌──────────────────┼───────────────────┐
          │                  │                   │
      MCP Tavily         MCP Firecrawl      MCP Browser
      búsqueda web        extracción         navegación
          │                  │                   │
          └──────────────┬──────────────────────┘
                         │
                  Leads encontrados
                         │
                  Normalización IA
                         │
                  PostgreSQL/MySQL
                         │
        IA puntúa calidad del lead (0-100)
                         │
         genera email personalizado EN/ES
                         │
             Gmail / SMTP / Brevo API
                         │
                  Seguimiento CRM

                  Paso 1. Buscar oportunidades

No usaría scraping como primera opción.

Primero buscaría mediante:

Tavily
Brave Search
Bing
Google Search API
Reddit
Hacker News
GitHub
LinkedIn (si tienes API o scraping permitido)
Wellfound
RemoteOK
Workana
Freelancer

Ejemplos de búsquedas:

Laravel developer needed

Need landing page developer

Hiring web developer

Need PHP developer

Need website redesign

Looking for freelancer website

En español:

Necesito desarrollador Laravel

Busco desarrollador web

Necesito landing page

Necesito página web

Busco programador PHP
Paso 2. Firecrawl

Cuando Tavily encuentra una URL:

https://empresa.com/jobs/laravel-developer

Firecrawl obtiene:

contenido limpio
emails
teléfonos
nombre empresa
descripción
enlaces

Todo sin tener que hacer scraping manual.

Paso 3. IA

El AI SDK analiza:

¿Es realmente un posible cliente?

SI

NO

Extrae:

Empresa

País

Idioma

Necesita Laravel

Necesita Landing

Presupuesto

Email

LinkedIn

Contacto

Prioridad

Y genera un JSON estructurado.

Paso 4. Score

Otro agente IA calcula:

Lead score

0-100

Por ejemplo:

Empresa pequeña
+20

Tiene presupuesto
+30

Necesita urgente
+20

Tiene email
+15

Acepta freelance
+15

Total = 100
Paso 5. Email automático

Otro agente genera:

Hola John,

He visto que buscáis un desarrollador Laravel...

...

En español o inglés según el sitio.

Paso 6. CRM

Guardar:

Lead

Estado

Fecha

Respuesta

Email enviado

Follow up

Notas IA

Scheduler

↓

Buscar en Tavily

↓

Encontrar URL

↓

Firecrawl limpia contenido

↓

IA extrae datos

↓

Guardar lead

↓

Eliminar duplicados

↓

Calcular score

↓

Generar email

↓

Enviar

↓

Esperar respuesta

↓

Follow-up automático

↓

Dashboard de métricas

Mi recomendación

Para este caso, una arquitectura Laravel-first es la más equilibrada:

Laravel gestiona la lógica de negocio, autenticación, colas, panel administrativo y el AI SDK.
MCP (Tavily, Firecrawl y Browser) aporta búsqueda y extracción de información.
Un microservicio en FastAPI solo si necesitas scraping complejo con Playwright o procesamiento intensivo que Laravel no maneje tan cómodamente.

Así mantienes una única aplicación principal, reduces la complejidad y solo introduces Python donde realmente aporta una ventaja técnica.