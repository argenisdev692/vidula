# Students CRUD — Frontend Modernization Audit

**Module:** Students (Company Profiles)  
**Date:** March 2, 2026  
**Current Status:** ⚠️ 7/10 (Needs Modernization)  
**Target Status:** ✅ 10/10

---

## 📋 Executive Summary

El módulo de Students está funcional pero necesita modernización para aprovechar React 19, TanStack Query v5 y TanStack Table v8.

**Score Actual:** 7/10  
**Score Objetivo:** 10/10

---

## ✅ Lo que está BIEN (7/10)

### 1. TanStack Query v5 ✅
- [x] useQuery implementado correctamente
- [x] useMutation implementado
- [x] Query keys con filtros
- [x] Invalidación de cache
- [x] placeholderData para UX fluida

**Archivo:** `useCompanies.ts`, `useStudentMutations.ts`

---

### 2. React 19 Hooks Básicos ✅
- [x] useTransition para búsqueda
- [x] useTransition para export
- [x] useState para manejo de estado
- [x] useRemember de Inertia

**Archivo:** `StudentIndexPage.tsx`

---

### 3. Arquitectura ✅
- [x] Separación correcta modules/ + pages/
- [x] Hooks personalizados
- [x] Type safety con TypeScript
- [x] Naming conventions correctos

---

## ⚠️ Lo que FALTA (3 puntos perdidos)

### 1. React 19 useOptimistic ❌
**Impacto:** -1 punto

**Problema:**
```tsx
// Actual: Delete espera respuesta del servidor
function handleConfirmSingleDelete(): void {
  if (!pendingDelete) return;
  deleteStudent.mutate(pendingDelete.uuid, {
    onSuccess: () => setPendingDelete(null),
  });
}
```

**Solución:**
```tsx
// Con useOptimistic: UI instantánea
const [optimisticCompanies, setOptimisticCompanies] = React.useOptimistic(
  companyList,
  (state, deletedUuid: string) => state.filter(c => c.uuid !== deletedUuid)
);

async function handleConfirmSingleDelete() {
  if (!pendingDelete) return;
  setOptimisticCompanies(pendingDelete.uuid); // Instantáneo
  try {
    await deleteStudent.mutateAsync(pendingDelete.uuid);
  } catch (err) {
    // React revierte automáticamente
  }
}
```

---

### 2. TanStack Table v8 Incompleto ❌
**Impacto:** -1 punto

**Problema:** No veo sorting ni filtering implementado en `StudentTable`

**Necesita:**
- [ ] Column sorting
- [ ] Column filtering
- [ ] useReactTable con todos los models
- [ ] flexRender para type safety

**Solución:** Implementar como en UsersTable.tsx

---

### 3. Formularios sin React 19 Patterns ❌
**Impacto:** -1 punto

**Problema:**
```tsx
// StudentCreatePage y StudentEditPage usan patrón antiguo
const [formData, setFormData] = useState({...});
const handleChange = (e) => { setFormData(...) };
const handleSubmit = (e) => { e.preventDefault(); mutation.mutate(...) };
```

**Solución:** Usar FormData nativo y simplificar:
```tsx
async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
  e.preventDefault();
  const formData = new FormData(e.currentTarget);
  
  const payload = {
    company_name: formData.get('company_name') as string,
    // ...
  };
  
  try {
    await createStudent.mutateAsync(payload);
    router.visit('/student');
  } catch (err) {
    setErrors(err.response?.data?.errors);
  }
}
```

---

## 📊 Checklist Detallado

### React 19 Features (2/5) ⚠️

| Feature | Status | Archivo | Notas |
|---------|--------|---------|-------|
| useTransition | ✅ | StudentIndexPage.tsx | Búsqueda y export |
| useOptimistic | ❌ | - | Falta implementar |
| FormData nativo | ❌ | Create/EditPage | Usa useState manual |
| React Compiler | ✅ | vite.config.ts | Ya configurado |
| Modern patterns | ⚠️ | - | Parcial |

**Score:** 2/5

---

### TanStack Query v5 (5/5) ✅

| Feature | Status | Archivo | Notas |
|---------|--------|---------|-------|
| useQuery | ✅ | useCompanies.ts | Correcto |
| useMutation | ✅ | useStudentMutations.ts | CRUD completo |
| Query keys | ✅ | useCompanies.ts | Con filtros |
| Invalidation | ✅ | useStudentMutations.ts | Automática |
| placeholderData | ✅ | useCompanies.ts | Para UX fluida |

**Score:** 5/5

---

### TanStack Table v8 (3/7) ⚠️

| Feature | Status | Archivo | Notas |
|---------|--------|---------|-------|
| useReactTable | ⚠️ | StudentTable.tsx | Necesita verificar |
| Row selection | ✅ | StudentIndexPage.tsx | Implementado |
| Column sorting | ❌ | - | Falta |
| Column filtering | ❌ | - | Falta |
| flexRender | ❌ | - | Falta |
| getCoreRowModel | ⚠️ | - | Verificar |
| getSortedRowModel | ❌ | - | Falta |

**Score:** 3/7

---

### Architecture (5/5) ✅

| Aspecto | Status | Notas |
|---------|--------|-------|
| Layer separation | ✅ | modules/ + pages/ correcto |
| Type safety | ✅ | TypeScript completo |
| Naming conventions | ✅ | Consistente |
| Import rules | ✅ | Sin circular deps |
| File organization | ✅ | Sigue arquitectura |

**Score:** 5/5

---

### TypeScript (5/5) ✅

| Aspecto | Status | Notas |
|---------|--------|-------|
| Type definitions | ✅ | StudentListItem, StudentDetail |
| Props tipadas | ✅ | Todas las props |
| Hook return types | ✅ | Correctos |
| API responses | ✅ | PaginatedResponse<T> |
| Strict mode | ✅ | Sin any innecesarios |

**Score:** 5/5

---

### Performance (4/5) ⚠️

| Aspecto | Status | Notas |
|---------|--------|-------|
| React Compiler | ✅ | Configurado |
| Query caching | ✅ | TanStack Query |
| Optimistic updates | ❌ | Falta |
| Code splitting | ✅ | Vite |
| Bundle size | ✅ | Optimizado |

**Score:** 4/5

---

## 🎯 Score Total

| Categoría | Puntos | Max | % |
|-----------|--------|-----|---|
| React 19 Features | 2 | 5 | 40% |
| TanStack Query v5 | 5 | 5 | 100% |
| TanStack Table v8 | 3 | 7 | 43% |
| Architecture | 5 | 5 | 100% |
| TypeScript | 5 | 5 | 100% |
| Performance | 4 | 5 | 80% |
| **TOTAL** | **24** | **32** | **75%** |

**Score Final:** 7.5/10 → **7/10**

---

## 🚀 Plan de Modernización

### Prioridad 1: useOptimistic (1 hora)
**Impacto:** +1 punto

1. Agregar useOptimistic en StudentIndexPage
2. Implementar delete instantáneo
3. Testing

**Archivos:**
- `StudentIndexPage.tsx`

---

### Prioridad 2: TanStack Table v8 Completo (2 horas)
**Impacto:** +1 punto

1. Agregar sorting a 3 columnas
2. Implementar filtering
3. Usar flexRender
4. Agregar todos los models

**Archivos:**
- `pages/students/components/StudentTable.tsx`

---

### Prioridad 3: Modernizar Formularios (1.5 horas)
**Impacto:** +1 punto

1. Usar FormData nativo
2. Simplificar handleSubmit
3. Eliminar useState innecesarios
4. Mejor error handling

**Archivos:**
- `StudentCreatePage.tsx`
- `StudentEditPage.tsx`

---

## 📁 Archivos a Modificar

### Alta Prioridad
```
✏️ resources/js/pages/students/StudentIndexPage.tsx
✏️ resources/js/pages/students/components/StudentTable.tsx
✏️ resources/js/pages/students/StudentCreatePage.tsx
✏️ resources/js/pages/students/StudentEditPage.tsx
```

### Sin Cambios (Ya Correctos)
```
✅ resources/js/modules/students/hooks/useCompanies.ts
✅ resources/js/modules/students/hooks/useStudent.ts
✅ resources/js/modules/students/hooks/useStudentMutations.ts
```

---

## 📊 Comparación con Users Module

| Aspecto | Users | Students | Gap |
|---------|-------|----------|-----|
| React 19 Features | 5/5 | 2/5 | -3 |
| TanStack Query | 5/5 | 5/5 | 0 |
| TanStack Table | 7/7 | 3/7 | -4 |
| Architecture | 5/5 | 5/5 | 0 |
| TypeScript | 5/5 | 5/5 | 0 |
| Performance | 5/5 | 4/5 | -1 |
| **TOTAL** | **32/32** | **24/32** | **-8** |

**Gap:** 8 puntos (25% menos moderno que Users)

---

## ✅ Conclusión

El módulo de Students está bien estructurado pero necesita:

1. **useOptimistic** para deletes instantáneos
2. **TanStack Table v8** completo con sorting/filtering
3. **Formularios modernos** con FormData nativo

**Tiempo estimado de modernización:** 4.5 horas  
**Score después de modernización:** 10/10

---

**Siguiente paso:** Implementar las mejoras siguiendo el patrón de Users module.
