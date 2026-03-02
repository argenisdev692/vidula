# Tablas CRM — Imagina Formación

> **Nota:** La tabla `users` ya existe en el proyecto con Laravel Spatie (roles y permisos). Todas las FK `user_id` referencian esa tabla existente. No se crea tabla nueva.

---

## 1. clients
Clientes a quienes se emiten las facturas (ej. Imagina Formación).

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| name | string | |
| email | string | nullable |
| phone | string | nullable |
| address | string | nullable |
| tax_id | string | NIF / CIF, nullable |
| company | string | nullable |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

---

## 2. products
Ítem central facturable. Puede ser un aula o un video tutorial.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| user_id | bigint | FK → users |
| type | string | classroom, video |
| title | string | |
| slug | string | único |
| description | text | nullable |
| price | decimal(10,2) | |
| currency | string(3) | default EUR |
| status | string | draft, published, archived |
| thumbnail | string | nullable |
| level | string | beginner, intermediate, advanced |
| language | string | default es |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

---

## 3. classrooms
Detalle específico de un producto de tipo aula.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| product_id | bigint | FK → products |
| modality | string | online, presential, hybrid |
| max_students | integer | nullable |
| start_date | date | nullable |
| end_date | date | nullable |
| duration_hours | integer | nullable |
| meet_url | string | link de clase, nullable |
| objectives | text | nullable |
| requirements | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 4. classroom_sections
Secciones o módulos dentro de un aula.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| classroom_id | bigint | FK → classrooms |
| title | string | |
| description | text | nullable |
| order | integer | orden de aparición |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 5. classroom_topics
Temas individuales dentro de cada sección del aula.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| classroom_section_id | bigint | FK → classroom_sections |
| title | string | |
| content | text | contenido markdown, nullable |
| type | string | lesson, exercise, quiz, resource |
| duration_minutes | integer | nullable |
| order | integer | orden de aparición |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 6. classroom_materials
Archivos y recursos adjuntos a un aula o tema específico.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| classroom_id | bigint | FK → classrooms |
| classroom_topic_id | bigint | FK → classroom_topics, nullable |
| title | string | |
| type | string | pdf, video, link, markdown, image |
| path | string | ruta en storage, nullable |
| url | string | enlace externo, nullable |
| content | text | markdown directo, nullable |
| order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 7. video_courses
Detalle específico de un producto de tipo video tutorial.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| product_id | bigint | FK → products |
| platform | string | youtube, vimeo, local, nullable |
| playlist_url | string | nullable |
| total_videos | integer | default 0 |
| total_duration_minutes | integer | nullable |
| target_audience | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 8. video_sections
Secciones dentro de un curso de video.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| video_course_id | bigint | FK → video_courses |
| title | string | |
| description | text | nullable |
| order | integer | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 9. video_topics
Temas individuales dentro de cada sección de video.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| video_section_id | bigint | FK → video_sections |
| title | string | |
| description | text | nullable |
| video_url | string | nullable |
| duration_minutes | integer | nullable |
| order | integer | |
| is_free_preview | boolean | default false |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 10. video_scripts
Guiones generados por cada tema de video.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| video_topic_id | bigint | FK → video_topics |
| title | string | |
| intro | text | introducción del video, nullable |
| body | text | desarrollo en markdown, nullable |
| outro | text | cierre y CTA, nullable |
| notes | text | notas del presentador, nullable |
| status | string | draft, reviewed, recorded |
| estimated_minutes | integer | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 11. invoices
Facturas emitidas a clientes.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| client_id | bigint | FK → clients |
| user_id | bigint | FK → users (instructor que emite) |
| number | string | único, ej. FAC-0001 |
| issue_date | date | fecha de emisión |
| due_date | date | fecha de vencimiento, nullable |
| subtotal | decimal(10,2) | |
| discount | decimal(10,2) | default 0 |
| total | decimal(10,2) | |
| currency | string(3) | default EUR |
| status | string | draft, sent, paid, cancelled |
| notes | text | nullable |
| pdf_path | string | ruta del PDF generado, nullable |
| paid_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

---

## 12. invoice_items
Líneas de detalle de cada factura.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| invoice_id | bigint | FK → invoices |
| product_id | bigint | FK → products, nullable |
| description | string | snapshot del nombre del producto |
| quantity | integer | default 1 |
| unit_price | decimal(10,2) | |
| discount | decimal(10,2) | default 0 |
| total | decimal(10,2) | |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## 13. students
Alumnos inscritos en las aulas.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| name | string | |
| email | string | único, nullable |
| phone | string | nullable |
| dni | string | documento de identidad, nullable |
| birth_date | date | nullable |
| address | string | nullable |
| avatar | string | nullable |
| notes | text | nullable |
| active | boolean | default true |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp | soft delete |

---

## 14. classroom_enrollments
Relación entre alumno y aula. Controla el estado de inscripción.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| student_id | bigint | FK → students |
| classroom_id | bigint | FK → classrooms |
| enrolled_at | date | fecha de inscripción |
| status | string | active, suspended, completed, dropped |
| final_grade | decimal(5,2) | promedio final calculado, nullable |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | | (student_id, classroom_id) |

---

## 15. classroom_attendances
Registro de asistencia por alumno por tema/clase.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| enrollment_id | bigint | FK → classroom_enrollments |
| classroom_topic_id | bigint | FK → classroom_topics |
| date | date | fecha de la clase |
| status | string | present, absent, late, justified |
| observation | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |
| UNIQUE | | (enrollment_id, classroom_topic_id) |

---

## 16. classroom_grades
Notas y calificaciones por alumno.

| Campo | Tipo | Notas |
|---|---|---|
| id | bigint | PK autoincrement |
| enrollment_id | bigint | FK → classroom_enrollments |
| classroom_topic_id | bigint | FK → classroom_topics, nullable |
| classroom_section_id | bigint | FK → classroom_sections, nullable |
| title | string | ej. Examen parcial, Tarea 1 |
| type | string | task, exam, participation, final |
| score | decimal(5,2) | nota obtenida |
| max_score | decimal(5,2) | nota máxima, default 100 |
| weight | decimal(5,2) | peso para promedio, default 1 |
| graded_at | date | |
| feedback | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

## Relaciones resumidas

```
users
 └── products (type: classroom | video)
       ├── classrooms
       │     ├── classroom_sections
       │     │     └── classroom_topics
       │     │           └── classroom_attendances
       │     ├── classroom_materials
       │     └── classroom_enrollments
       │           └── classroom_grades
       └── video_courses
             └── video_sections
                   └── video_topics
                         └── video_scripts

clients
 └── invoices
       └── invoice_items → products

students
 └── classroom_enrollments → classrooms
```
